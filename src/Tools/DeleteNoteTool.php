<?php

namespace Platform\Notes\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notes\Models\NotesNote;

/**
 * Tool zum Löschen von Notizen.
 */
class DeleteNoteTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.notes.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /notes/notes/{id} - Löscht eine Notiz. '
            . 'Parameter: id (required), confirm (optional). '
            . 'Hinweis: Löschen ist soft (wiederherstellbar).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID der Notiz (ERFORDERLICH). Nutze "notes.notes.GET" um die Notiz zu prüfen.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass die Notiz wirklich gelöscht werden soll.',
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
                return ToolResult::error('Notiz-ID ist erforderlich', 'VALIDATION_ERROR');
            }

            $note = NotesNote::withTrashed()->find($id);
            if (!$note) {
                return ToolResult::error('Notiz nicht gefunden.', 'NOT_FOUND');
            }

            if ($note->trashed()) {
                return ToolResult::error('Die Notiz wurde bereits gelöscht.', 'ALREADY_DELETED');
            }

            if ($context->team && $note->team_id !== $context->team->id) {
                return ToolResult::error('Notiz gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            try {
                Gate::forUser($context->user)->authorize('delete', $note);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst diese Notiz nicht löschen (Policy).', 'ACCESS_DENIED');
            }

            $hasContent = trim((string) ($note->content ?? '')) !== '';
            if ($hasContent && !($arguments['confirm'] ?? false)) {
                return ToolResult::error(
                    "Die Notiz '{$note->name}' enthält Inhalt. Bitte bestätige die Löschung mit 'confirm: true'.",
                    'CONFIRMATION_REQUIRED'
                );
            }

            $noteId = $note->id;
            $noteName = $note->name;
            $folderId = $note->folder_id;

            $note->delete();

            return ToolResult::success([
                'id' => $noteId,
                'name' => $noteName,
                'folder_id' => $folderId,
                'message' => "Notiz '{$noteName}' wurde gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Löschen der Notiz: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['notes', 'note', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}

