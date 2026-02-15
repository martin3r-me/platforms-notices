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
 * Tool zum Abrufen der Berechtigungen eines Ordners.
 */
class GetFolderPermissionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.folder_permissions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notes/folders/{id}/permissions - Liefert alle Berechtigungen (User + Rollen) eines Ordners. '
            . 'Parameter: id (required). '
            . 'Rückgabe: Liste aller User mit ihren Rollen (owner, admin, member, viewer).';
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

            $folder = NotesFolder::query()->find($id);
            if (!$folder) {
                return ToolResult::error('Ordner nicht gefunden.', 'NOT_FOUND');
            }

            // Team-Check
            if ($context->team && $folder->team_id !== $context->team->id) {
                return ToolResult::error('Ordner gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            // Policy: Nur wer den Ordner sehen darf, darf auch die Berechtigungen sehen
            try {
                Gate::forUser($context->user)->authorize('view', $folder);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst diesen Ordner nicht sehen (Policy).', 'ACCESS_DENIED');
            }

            // Berechtigungen laden
            $folderUsers = $folder->folderUsers()->with('user')->get();

            $permissions = [];
            foreach ($folderUsers as $folderUser) {
                $permissions[] = [
                    'user_id' => $folderUser->user_id,
                    'user_name' => $folderUser->user?->name ?? 'Unbekannt',
                    'user_email' => $folderUser->user?->email ?? null,
                    'role' => $folderUser->role,
                ];
            }

            // Owner hinzufügen, falls nicht bereits in folderUsers
            if ($folder->user_id && !$folderUsers->contains('user_id', $folder->user_id)) {
                $ownerUser = \Platform\Core\Models\User::find($folder->user_id);
                if ($ownerUser) {
                    array_unshift($permissions, [
                        'user_id' => $ownerUser->id,
                        'user_name' => $ownerUser->name,
                        'user_email' => $ownerUser->email,
                        'role' => FolderRole::OWNER->value,
                    ]);
                }
            }

            return ToolResult::success([
                'folder_id' => $folder->id,
                'folder_name' => $folder->name,
                'permissions' => $permissions,
                'available_roles' => [
                    FolderRole::ADMIN->value,
                    FolderRole::MEMBER->value,
                    FolderRole::VIEWER->value,
                ],
                'message' => count($permissions) . ' Berechtigung(en) für Ordner "' . $folder->name . '" gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Abrufen der Berechtigungen: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['notes', 'folder', 'permissions', 'get', 'read'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'read',
            'idempotent' => true,
            'side_effects' => [],
        ];
    }
}
