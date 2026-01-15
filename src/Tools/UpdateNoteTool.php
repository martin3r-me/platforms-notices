<?php

namespace Platform\Notes\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notes\Models\NotesNote;

/**
 * Tool zum Aktualisieren von Notizen (Titel/Inhalt)
 */
class UpdateNoteTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.notes.PATCH';
    }

    public function getDescription(): string
    {
        return 'PATCH /notes/notes/{id} - Aktualisiert eine Notiz (direktes Setzen einzelner Felder). '
            . 'Parameter: id (required), name (optional), content (optional markdown). '
            . 'WICHTIG: Sende niemals content=null; null wird ignoriert. '
            . 'Für sichere Teil-Änderungen (append/replace/section) nutze notes.notes.EDIT. '
            . 'Empfohlen: replace_between mit stabilen Markern.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID der Notiz (ERFORDERLICH).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Markdown-Inhalt.',
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

            $note = NotesNote::query()->find($id);
            if (!$note) {
                return ToolResult::error('Notiz nicht gefunden.', 'NOT_FOUND');
            }

            // Team-Check (sicherstellen, dass Kontext passt)
            if ($context->team && $note->team_id !== $context->team->id) {
                return ToolResult::error('Notiz gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            // Policy
            try {
                Gate::forUser($context->user)->authorize('update', $note);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst diese Notiz nicht ändern (Policy).', 'ACCESS_DENIED');
            }

            $payload = [];
            // WICHTIG: Viele LLMs schicken optionale Felder als null -> das darf NICHT den Inhalt löschen.
            if (array_key_exists('name', $arguments) && $arguments['name'] !== null) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('Titel darf nicht leer sein.', 'VALIDATION_ERROR');
                }
                $payload['name'] = $name;
            }
            // content darf bewusst leerer String sein (um zu leeren), aber null wird ignoriert
            if (array_key_exists('content', $arguments) && $arguments['content'] !== null) {
                $payload['content'] = (string) $arguments['content'];
            }

            if (empty($payload)) {
                return ToolResult::success([
                    'id' => $note->id,
                    'message' => 'Keine Änderungen übergeben.',
                ]);
            }

            $note->update($payload);

            return ToolResult::success([
                'id' => $note->id,
                'uuid' => $note->uuid,
                'name' => $note->name,
                'team_id' => $note->team_id,
                'updated_at' => $note->updated_at?->toIso8601String(),
                'message' => "Notiz '{$note->name}' erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Aktualisieren der Notiz: ' . $e->getMessage(), 'EXECUTION_ERROR');
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
            'tags' => ['notes', 'note', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['updates'],
        ];
    }
}

