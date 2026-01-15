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
 * Tool zum Abrufen einer Notiz inkl. Inhalt.
 */
class GetNoteTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.notes.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notes/notes/{id} - Liefert eine Notiz (Titel + Markdown-Inhalt).';
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

            // Team-Check
            if ($context->team && $note->team_id !== $context->team->id) {
                return ToolResult::error('Notiz gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            // Policy
            try {
                Gate::forUser($context->user)->authorize('view', $note);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst diese Notiz nicht sehen (Policy).', 'ACCESS_DENIED');
            }

            $content = (string) ($note->content ?? '');

            return ToolResult::success([
                'id' => $note->id,
                'uuid' => $note->uuid,
                'name' => $note->name,
                'folder_id' => $note->folder_id,
                'team_id' => $note->team_id,
                'content' => $content,
                'updated_at' => $note->updated_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Abrufen der Notiz: ' . $e->getMessage(), 'EXECUTION_ERROR');
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
            'category' => 'read',
            'tags' => ['notes', 'note', 'get', 'read'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'read',
            'idempotent' => true,
            'side_effects' => [],
        ];
    }
}

