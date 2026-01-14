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

/**
 * Tool zum Erstellen von Ordnern im Notes-Modul
 */
class CreateFolderTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.folders.POST';
    }

    public function getDescription(): string
    {
        return 'POST /notes/folders - Erstellt einen neuen Ordner (optional als Unterordner). Parameter: name (required), team_id (optional; default aktuelles Team), parent_id (optional; Parent-Ordner), description (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Ordners (ERFORDERLICH).',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Wenn nicht gesetzt, wird das aktuelle Team aus dem Kontext verwendet.',
                ],
                'parent_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Parent-Ordner-ID (für Unterordner).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Ordners.',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (empty($arguments['name'])) {
                return ToolResult::error('Ordnername ist erforderlich', 'VALIDATION_ERROR');
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
                Gate::forUser($context->user)->authorize('create', NotesFolder::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst keine Ordner erstellen (Policy).', 'ACCESS_DENIED');
            }

            // Parent prüfen (falls gesetzt)
            $parentId = $arguments['parent_id'] ?? null;
            $parent = null;
            if (!empty($parentId)) {
                $parent = NotesFolder::query()->find($parentId);
                if (!$parent) {
                    return ToolResult::error('Parent-Ordner nicht gefunden.', 'PARENT_NOT_FOUND');
                }
                if ($parent->team_id !== $team->id) {
                    return ToolResult::error('Parent-Ordner gehört zu einem anderen Team.', 'TEAM_MISMATCH');
                }
                try {
                    Gate::forUser($context->user)->authorize('view', $parent);
                } catch (AuthorizationException $e) {
                    return ToolResult::error('Kein Zugriff auf den Parent-Ordner.', 'ACCESS_DENIED');
                }
            }

            $folder = NotesFolder::create([
                'name' => $arguments['name'],
                'description' => $arguments['description'] ?? null,
                'parent_id' => $parent?->id,
                'user_id' => $context->user->id,
                'team_id' => $team->id,
            ]);

            return ToolResult::success([
                'id' => $folder->id,
                'uuid' => $folder->uuid,
                'name' => $folder->name,
                'description' => $folder->description,
                'parent_id' => $folder->parent_id,
                'team_id' => $folder->team_id,
                'created_at' => $folder->created_at?->toIso8601String(),
                'message' => "Ordner '{$folder->name}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Erstellen des Ordners: ' . $e->getMessage(), 'EXECUTION_ERROR');
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
            'tags' => ['notes', 'folder', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}

