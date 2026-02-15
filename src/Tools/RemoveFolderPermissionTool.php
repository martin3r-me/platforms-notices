<?php

namespace Platform\Notes\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesFolderUser;
use Platform\Notes\Enums\FolderRole;

/**
 * Tool zum Entfernen von Ordner-Berechtigungen (User aus Ordner entfernen).
 */
class RemoveFolderPermissionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.folder_permissions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /notes/folders/{id}/permissions/{user_id} - Entfernt die Berechtigung eines Users für einen Ordner. '
            . 'Parameter: id (required), user_id (required). '
            . 'Hinweis: Nur Owner und Admins dürfen Berechtigungen entfernen. Der Owner selbst kann nicht entfernt werden.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID des Ordners (ERFORDERLICH).',
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Users, dessen Berechtigung entfernt werden soll (ERFORDERLICH).',
                ],
            ],
            'required' => ['id', 'user_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $id = $arguments['id'] ?? null;
            $targetUserId = $arguments['user_id'] ?? null;

            if (empty($id)) {
                return ToolResult::error('Ordner-ID ist erforderlich', 'VALIDATION_ERROR');
            }
            if (empty($targetUserId)) {
                return ToolResult::error('User-ID ist erforderlich', 'VALIDATION_ERROR');
            }

            $folder = NotesFolder::query()->find($id);
            if (!$folder) {
                return ToolResult::error('Ordner nicht gefunden.', 'NOT_FOUND');
            }

            // Team-Check
            if ($context->team && $folder->team_id !== $context->team->id) {
                return ToolResult::error('Ordner gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            // Policy: Nur Owner/Admin dürfen Mitglieder entfernen
            try {
                Gate::forUser($context->user)->authorize('removeMember', $folder);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst keine Berechtigungen aus diesem Ordner entfernen (Policy).', 'ACCESS_DENIED');
            }

            // Eintrag finden
            $folderUser = NotesFolderUser::where('folder_id', $folder->id)
                ->where('user_id', $targetUserId)
                ->first();

            if (!$folderUser) {
                return ToolResult::error('User hat keine direkte Berechtigung für diesen Ordner.', 'NOT_FOUND');
            }

            // Owner kann nicht entfernt werden
            if ($folderUser->role === FolderRole::OWNER->value) {
                return ToolResult::error('Der Owner kann nicht entfernt werden.', 'VALIDATION_ERROR');
            }

            $removedRole = $folderUser->role;
            $folderUser->delete();

            return ToolResult::success([
                'folder_id' => $folder->id,
                'folder_name' => $folder->name,
                'user_id' => $targetUserId,
                'removed_role' => $removedRole,
                'message' => "Berechtigung '{$removedRole}' für User #{$targetUserId} wurde entfernt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Entfernen der Berechtigung: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['notes', 'folder', 'permissions', 'delete', 'remove'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}
