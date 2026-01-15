<?php

namespace Platform\Notes\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notes\Models\NotesNote;

/**
 * Sicheres Edit-Tool: führt Teil-Änderungen am Markdown aus, ohne den gesamten Inhalt zu überschreiben.
 */
class EditNoteTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notes.notes.EDIT';
    }

    public function getDescription(): string
    {
        return "EDIT notes note content safely. Use this instead of PUT/PATCH when possible.\n"
            . "Ops: append, prepend, replace_exact, replace_between, upsert_heading.\n"
            . "Rules: null fields are ignored; if replacement target is missing/ambiguous -> returns error (no write).";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Notiz-ID (ERFORDERLICH).'],
                'op' => [
                    'type' => 'string',
                    'description' => 'Edit-Operation: append | prepend | replace_exact | replace_between | upsert_heading',
                    'enum' => ['append', 'prepend', 'replace_exact', 'replace_between', 'upsert_heading'],
                ],
                'text' => ['type' => 'string', 'description' => 'Text/Markdown, der eingefügt/gesetzt werden soll (je nach op).'],
                'old' => ['type' => 'string', 'description' => 'Für replace_exact: exakt zu ersetzender Block (muss genau einmal vorkommen).'],
                'new' => ['type' => 'string', 'description' => 'Für replace_exact/replace_between: neuer Block.'],
                'start_marker' => ['type' => 'string', 'description' => 'Für replace_between: Start-Marker (wird im Text gesucht).'],
                'end_marker' => ['type' => 'string', 'description' => 'Für replace_between: End-Marker (wird im Text gesucht).'],
                'heading' => ['type' => 'string', 'description' => 'Für upsert_heading: Überschriftstext ohne # (z.B. \"Notizen\").'],
                'level' => ['type' => 'integer', 'description' => 'Für upsert_heading: Heading-Level 1-6 (Default 2).'],
                'mode' => [
                    'type' => 'string',
                    'description' => 'Für upsert_heading: replace (ersetzen) | append (anhängen) (Default append).',
                    'enum' => ['replace', 'append'],
                ],
            ],
            'required' => ['id', 'op'],
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

            if ($context->team && $note->team_id !== $context->team->id) {
                return ToolResult::error('Notiz gehört zu einem anderen Team.', 'TEAM_MISMATCH');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $note);
            } catch (AuthorizationException $e) {
                return ToolResult::error('Du darfst diese Notiz nicht ändern (Policy).', 'ACCESS_DENIED');
            }

            $op = (string) ($arguments['op'] ?? '');
            $content = (string) ($note->content ?? '');

            $result = match ($op) {
                'append' => $this->append($content, (string) ($arguments['text'] ?? '')),
                'prepend' => $this->prepend($content, (string) ($arguments['text'] ?? '')),
                'replace_exact' => $this->replaceExact(
                    $content,
                    $arguments['old'] ?? null,
                    $arguments['new'] ?? null
                ),
                'replace_between' => $this->replaceBetween(
                    $content,
                    $arguments['start_marker'] ?? null,
                    $arguments['end_marker'] ?? null,
                    $arguments['new'] ?? null
                ),
                'upsert_heading' => $this->upsertHeading(
                    $content,
                    $arguments['heading'] ?? null,
                    (string) ($arguments['text'] ?? ''),
                    (int) ($arguments['level'] ?? 2),
                    (string) ($arguments['mode'] ?? 'append')
                ),
                default => ToolResult::error('Unbekannte op: ' . $op, 'VALIDATION_ERROR'),
            };

            if (!$result->success) {
                return $result;
            }

            $newContent = (string) $result->data['content'];
            if ($newContent === $content) {
                return ToolResult::success([
                    'id' => $note->id,
                    'message' => 'Keine Änderung (Inhalt unverändert).',
                ]);
            }

            $note->update(['content' => $newContent]);

            return ToolResult::success([
                'id' => $note->id,
                'uuid' => $note->uuid,
                'updated_at' => $note->updated_at?->toIso8601String(),
                'message' => 'Notiz erfolgreich bearbeitet.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('Fehler beim Bearbeiten der Notiz: ' . $e->getMessage(), 'EXECUTION_ERROR');
        }
    }

    private function append(string $content, string $text): ToolResult
    {
        $text = rtrim($text);
        if ($text === '') {
            return ToolResult::error('text ist erforderlich für append.', 'VALIDATION_ERROR');
        }

        $content = rtrim($content);
        $out = $content === '' ? $text : ($content . "\n\n" . $text);
        return ToolResult::success(['content' => $out]);
    }

    private function prepend(string $content, string $text): ToolResult
    {
        $text = rtrim($text);
        if ($text === '') {
            return ToolResult::error('text ist erforderlich für prepend.', 'VALIDATION_ERROR');
        }

        $content = ltrim($content);
        $out = $content === '' ? $text : ($text . "\n\n" . $content);
        return ToolResult::success(['content' => $out]);
    }

    private function replaceExact(string $content, mixed $old, mixed $new): ToolResult
    {
        if ($old === null || $new === null) {
            return ToolResult::error('old und new sind erforderlich für replace_exact.', 'VALIDATION_ERROR');
        }
        $old = (string) $old;
        $new = (string) $new;

        if ($old === '') {
            return ToolResult::error('old darf nicht leer sein.', 'VALIDATION_ERROR');
        }

        $count = substr_count($content, $old);
        if ($count === 0) {
            return ToolResult::error('Der zu ersetzende Block (old) wurde nicht gefunden.', 'TARGET_NOT_FOUND');
        }
        if ($count > 1) {
            return ToolResult::error('Der zu ersetzende Block (old) ist nicht eindeutig (kommt mehrfach vor).', 'TARGET_AMBIGUOUS');
        }

        return ToolResult::success(['content' => str_replace($old, $new, $content)]);
    }

    private function replaceBetween(string $content, mixed $start, mixed $end, mixed $new): ToolResult
    {
        if ($start === null || $end === null || $new === null) {
            return ToolResult::error('start_marker, end_marker und new sind erforderlich für replace_between.', 'VALIDATION_ERROR');
        }

        $start = (string) $start;
        $end = (string) $end;
        $new = (string) $new;

        $startPos = strpos($content, $start);
        if ($startPos === false) {
            return ToolResult::error('start_marker nicht gefunden.', 'TARGET_NOT_FOUND');
        }
        $endPos = strpos($content, $end, $startPos + strlen($start));
        if ($endPos === false) {
            return ToolResult::error('end_marker nicht gefunden.', 'TARGET_NOT_FOUND');
        }

        $before = substr($content, 0, $startPos + strlen($start));
        $after = substr($content, $endPos);

        // Inhalt zwischen den Markern ersetzen, Marker bleiben erhalten
        $out = rtrim($before) . "\n\n" . rtrim($new) . "\n\n" . ltrim($after);
        return ToolResult::success(['content' => $out]);
    }

    private function upsertHeading(string $content, mixed $heading, string $text, int $level, string $mode): ToolResult
    {
        if ($heading === null) {
            return ToolResult::error('heading ist erforderlich für upsert_heading.', 'VALIDATION_ERROR');
        }
        $heading = trim((string) $heading);
        if ($heading === '') {
            return ToolResult::error('heading darf nicht leer sein.', 'VALIDATION_ERROR');
        }
        $text = rtrim($text);
        if ($text === '') {
            return ToolResult::error('text ist erforderlich für upsert_heading.', 'VALIDATION_ERROR');
        }
        if ($level < 1 || $level > 6) $level = 2;
        $mode = $mode === 'replace' ? 'replace' : 'append';

        $hashes = str_repeat('#', $level);
        $needle = $hashes . ' ' . $heading;

        // Suche Heading-Start
        $pos = strpos($content, $needle);
        if ($pos === false) {
            // Heading existiert nicht -> ans Ende anfügen
            $out = rtrim($content);
            $block = $needle . "\n\n" . $text;
            $out = $out === '' ? $block : ($out . "\n\n" . $block);
            return ToolResult::success(['content' => $out]);
        }

        // Abschnitt bis zur nächsten Überschrift gleicher/kleinerer Ebene bestimmen
        $afterHeadingPos = $pos + strlen($needle);
        $rest = substr($content, $afterHeadingPos);

        // Regex: nächste Heading mit 1..$level # am Zeilenanfang
        $pattern = '/\n#{1,' . $level . '}\s+/';
        if (preg_match($pattern, $rest, $m, PREG_OFFSET_CAPTURE)) {
            $nextRel = $m[0][1];
            $section = substr($content, $afterHeadingPos, $nextRel);
            $tail = substr($content, $afterHeadingPos + $nextRel);
        } else {
            $section = substr($content, $afterHeadingPos);
            $tail = '';
        }

        if ($mode === 'replace') {
            $newSection = "\n\n" . $text . "\n";
        } else {
            $trimmed = rtrim($section);
            $newSection = ($trimmed === '' ? "\n\n" . $text . "\n" : $trimmed . "\n\n" . $text . "\n");
        }

        $out = substr($content, 0, $afterHeadingPos) . $newSection . ltrim($tail, "\n");
        return ToolResult::success(['content' => $out]);
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
            'tags' => ['notes', 'note', 'edit', 'safe-update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['updates'],
        ];
    }
}

