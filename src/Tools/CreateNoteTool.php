<?php

namespace Platform\Notes\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;

/**
 * Tool zum Erstellen von Notizen (Markdown) im Notes-Modul
 */
class CreateNoteTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.notes.POST';
    }

    public function getDescription(): string
    {
        return 'POST /notes/notes - Erstellt eine neue Markdown-Notiz. Parameter: name (required), content (optional markdown), team_id (optional; default aktuelles Team), folder_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Titel/Name der Notiz (ERFORDERLICH).',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Optional: Markdown-Inhalt.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Wenn nicht gesetzt, wird das aktuelle Team aus dem Kontext verwendet.',
                ],
                'folder_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Ordner-ID, in dem die Notiz angelegt werden soll.',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (empty($arguments['name'])) {
                return ToolResult::error('Notiz-Titel ist erforderlich', 'VALIDATION_ERROR');
            }

            // Team bestimmen
            $teamId = $arguments['team_id'] ?? null;
            if ($teamId === 0 || $teamId === '0') {
                $teamId = null;
            }

            $team = null;
            if (!empty($teamId)) {
                $team = $context->user->teams()->find($teamId);
                if (!$team) {
                    return ToolResult::error('Team nicht gefunden oder kein Zugriff.', 'TEAM_NOT_FOUND');
                }
            } else {
                $team = $context->team;
                if (!$team) {
                    return ToolResult::error('Kein Team angegeben und kein Team im Kontext.', 'MISSING_TEAM');
                }
            }

            // Policy
            try {
                Gate::forUser($context->user)->authorize('create', NotesNote::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst keine Notizen erstellen (Policy).', 'ACCESS_DENIED');
            }

            // Folder prüfen (falls gesetzt)
            $folderId = $arguments['folder_id'] ?? null;
            $folder = null;
            if (!empty($folderId)) {
                $folder = NotesFolder::query()->find($folderId);
                if (!$folder) {
                    return ToolResult::error('Ordner nicht gefunden.', 'FOLDER_NOT_FOUND');
                }
                if ($folder->team_id !== $team->id) {
                    return ToolResult::error('Ordner gehört zu einem anderen Team.', 'TEAM_MISMATCH');
                }
                try {
                    Gate::forUser($context->user)->authorize('view', $folder);
                } catch (AuthorizationException $e) {
                    return ToolResult::error('Kein Zugriff auf den Ordner.', 'ACCESS_DENIED');
                }
            }

            $note = NotesNote::create([
                'name' => $arguments['name'],
                'content' => $arguments['content'] ?? '',
                'folder_id' => $folder?->id,
                'user_id' => $context->user->id,
                'team_id' => $team->id,
            ]);

            return ToolResult::success([
                'id' => $note->id,
                'uuid' => $note->uuid,
                'name' => $note->name,
                'folder_id' => $note->folder_id,
                'team_id' => $note->team_id,
                'created_at' => $note->created_at?->toIso8601String(),
                'message' => "Notiz '{$note->name}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Erstellen der Notiz: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    public function getDependencies(): array
    {
        return [
            'required_fields' => [],
            'dependencies' => [],
        ];
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['notes', 'note', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

