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
 * Tool zum Setzen/Ändern von Ordner-Berechtigungen (User hinzufügen oder Rolle ändern).
 */
class SetFolderPermissionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.folder_permissions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /notes/folders/{id}/permissions - Setzt oder ändert die Berechtigung eines Users für einen Ordner. '
            . 'Parameter: id (required), user_id (required), role (required: admin|member|viewer). '
            . 'Hinweis: Nur Owner und Admins dürfen Berechtigungen setzen. Rollenänderungen nur durch Owner.';
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
                    'description' => 'ID des Users, dessen Berechtigung gesetzt werden soll (ERFORDERLICH).',
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'Rolle für den User (ERFORDERLICH). Erlaubte Werte: admin, member, viewer.',
                    'enum' => ['admin', 'member', 'viewer'],
                ],
            ],
            'required' => ['id', 'user_id', 'role'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $id = $arguments['id'] ?? null;
            $targetUserId = $arguments['user_id'] ?? null;
            $role = $arguments['role'] ?? null;

            if (empty($id)) {
                return ToolResult::error('Ordner-ID ist erforderlich', 'VALIDATION_ERROR');
            }
            if (empty($targetUserId)) {
                return ToolResult::error('User-ID ist erforderlich', 'VALIDATION_ERROR');
            }
            if (empty($role)) {
                return ToolResult::error('Rolle ist erforderlich', 'VALIDATION_ERROR');
            }

            // Rolle validieren
            $allowedRoles = [
                FolderRole::ADMIN->value,
                FolderRole::MEMBER->value,
                FolderRole::VIEWER->value,
            ];
            if (!in_array($role, $allowedRoles, true)) {
                return ToolResult::error(
                    'Ungültige Rolle. Erlaubte Werte: ' . implode(', ', $allowedRoles),
                    'VALIDATION_ERROR'
                );
            }

            $folder = NotesFolder::query()->find($id);
            if (!$folder) {
                return ToolResult::error('Ordner nicht gefunden.', 'NOT_FOUND');
            }

            // Team-Check
            if ($context->team && $folder->team_id !== $context->team->id) {
                return ToolResult::error('Ordner gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            // Prüfen ob bereits ein Eintrag existiert
            $existing = NotesFolderUser::where('folder_id', $folder->id)
                ->where('user_id', $targetUserId)
                ->first();

            if ($existing) {
                // Rollenänderung: nur Owner darf Rollen ändern
                try {
                    Gate::forUser($context->user)->authorize('changeRole', $folder);
                } catch (AuthorizationException $e) {
                    return ToolResult::error('Nur der Owner darf Rollen ändern (Policy).', 'ACCESS_DENIED');
                }

                // Owner-Rolle kann nicht geändert werden
                if ($existing->role === FolderRole::OWNER->value) {
                    return ToolResult::error('Die Owner-Rolle kann nicht geändert werden.', 'VALIDATION_ERROR');
                }

                $existing->update(['role' => $role]);

                return ToolResult::success([
                    'folder_id' => $folder->id,
                    'folder_name' => $folder->name,
                    'user_id' => $targetUserId,
                    'role' => $role,
                    'action' => 'updated',
                    'message' => "Rolle für User #{$targetUserId} wurde auf '{$role}' geändert.",
                ]);
            } else {
                // Neuen User hinzufügen: Owner/Admin dürfen einladen
                try {
                    Gate::forUser($context->user)->authorize('invite', $folder);
                } catch (AuthorizationException $e) {
                    return ToolResult::error('Du darfst keine User zu diesem Ordner einladen (Policy).', 'ACCESS_DENIED');
                }

                // Prüfen ob der Ziel-User existiert und im Team ist
                $targetUser = \Platform\Core\Models\User::find($targetUserId);
                if (!$targetUser) {
                    return ToolResult::error('User nicht gefunden.', 'USER_NOT_FOUND');
                }

                // Team-Mitgliedschaft prüfen
                $team = $folder->team;
                if ($team && !$team->users()->where('users.id', $targetUserId)->exists()) {
                    return ToolResult::error('Der User ist kein Mitglied des Teams.', 'TEAM_MISMATCH');
                }

                NotesFolderUser::create([
                    'folder_id' => $folder->id,
                    'user_id' => $targetUserId,
                    'role' => $role,
                ]);

                return ToolResult::success([
                    'folder_id' => $folder->id,
                    'folder_name' => $folder->name,
                    'user_id' => $targetUserId,
                    'role' => $role,
                    'action' => 'created',
                    'message' => "User #{$targetUserId} wurde mit Rolle '{$role}' zum Ordner hinzugefügt.",
                ]);
            }
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Setzen der Berechtigung: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['notes', 'folder', 'permissions', 'set', 'update', 'invite'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates', 'updates'],
        ];
    }
}
