<?php

namespace Platform\Notes\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notes\Models\NotesFolder;

/**
 * Tool zum Löschen von Ordnern (inkl. Unterordner und Notizen).
 */
class DeleteFolderTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.folders.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /notes/folders/{id} - Löscht einen Ordner. '
            . 'Parameter: id (required), confirm (optional). '
            . 'Hinweis: Löscht den Ordner inkl. Unterordner und Notizen (soft delete).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID des Ordners (ERFORDERLICH). Nutze "notes.folders.GET" um Ordner zu finden.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass der Ordner inkl. Unterordner/Notizen gelöscht werden soll.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $id = $arguments['id'] ?? null;
            if (empty($id)) {
                return ToolResult::error('Ordner-ID ist erforderlich', 'VALIDATION_ERROR');
            }

            $folder = NotesFolder::withTrashed()->find($id);
            if (!$folder) {
                return ToolResult::error('Ordner nicht gefunden.', 'NOT_FOUND');
            }

            if ($folder->trashed()) {
                return ToolResult::error('Der Ordner wurde bereits gelöscht.', 'ALREADY_DELETED');
            }

            if ($context->team && $folder->team_id !== $context->team->id) {
                return ToolResult::error('Ordner gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            try {
                Gate::forUser($context->user)->authorize('delete', $folder);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst diesen Ordner nicht löschen (Policy).', 'ACCESS_DENIED');
            }

            $counts = $this->countFolderTree($folder);
            $hasContents = ($counts['folders'] > 0 || $counts['notes'] > 0);
            if ($hasContents && !($arguments['confirm'] ?? false)) {
                return ToolResult::error(
                    "Der Ordner enthält {$counts['folders']} Unterordner und {$counts['notes']} Notiz(en). "
                        . "Bitte bestätige die Löschung mit 'confirm: true'.",
                    'CONFIRMATION_REQUIRED'
                );
            }

            $folderId = $folder->id;
            $folderName = $folder->name;

            $deleted = $this->deleteFolderTree($folder);

            return ToolResult::success([
                'id' => $folderId,
                'name' => $folderName,
                'deleted_folders' => $deleted['folders'],
                'deleted_notes' => $deleted['notes'],
                'message' => "Ordner '{$folderName}' wurde gelöscht (inkl. Unterordner/Notizen).",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Löschen des Ordners: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    private function countFolderTree(NotesFolder $folder): array
    {
        $folders = 0;
        $notes = $folder->notes()->count();

        foreach ($folder->children as $child) {
            $folders++;
            $childCounts = $this->countFolderTree($child);
            $folders += $childCounts['folders'];
            $notes += $childCounts['notes'];
        }

        return ['folders' => $folders, 'notes' => $notes];
    }

    private function deleteFolderTree(NotesFolder $folder): array
    {
        $folders = 0;
        $notes = 0;

        foreach ($folder->children as $child) {
            $childDeleted = $this->deleteFolderTree($child);
            $folders += $childDeleted['folders'];
            $notes += $childDeleted['notes'];
        }

        $notes += $folder->notes()->count();
        $folder->notes()->delete();

        $folder->delete();
        $folders++;

        return ['folders' => $folders, 'notes' => $notes];
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['notes', 'folder', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}

