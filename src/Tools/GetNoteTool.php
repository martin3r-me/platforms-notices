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
 * Tool zum Abrufen einer Notiz inkl. Inhalt (optional mit Zeilennummern).
 */
class GetNoteTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.notes.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notes/notes/{id} - Liefert eine Notiz (Titel + Markdown-Inhalt). Optional: include_lines=true für Zeilennummern, damit Änderungen präzise erfolgen können.';
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
                'include_lines' => [
                    'type' => 'boolean',
                    'description' => 'Wenn true: Inhalt zusätzlich als Liste mit Zeilennummern zurückgeben (für präzise Edits).',
                ],
                'max_lines' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximalzahl Zeilen für include_lines (Default 800).',
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
            $includeLines = (bool) ($arguments['include_lines'] ?? false);
            $maxLines = (int) ($arguments['max_lines'] ?? 800);
            if ($maxLines <= 0) $maxLines = 800;

            $lines = null;
            if ($includeLines) {
                $rawLines = preg_split("/\r\n|\n|\r/", $content);
                $rawLines = $rawLines === false ? [] : $rawLines;
                $rawLines = array_slice($rawLines, 0, $maxLines);
                $lines = [];
                foreach ($rawLines as $i => $line) {
                    $lines[] = [
                        'no' => $i + 1,
                        'text' => $line,
                    ];
                }
            }

            return ToolResult::success([
                'id' => $note->id,
                'uuid' => $note->uuid,
                'name' => $note->name,
                'folder_id' => $note->folder_id,
                'team_id' => $note->team_id,
                'content' => $content,
                'content_lines' => $lines,
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

