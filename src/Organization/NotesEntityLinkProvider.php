<?php

namespace Platform\Notes\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Notes\Models\NotesNote;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class NotesEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['notes_note'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'notes_note' => ['label' => 'Notizen', 'singular' => 'Notiz', 'icon' => 'document-text', 'route' => null],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        // No eager loading needed
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'is_done' => (bool) ($model->done ?? false),
            'is_pinned' => (bool) ($model->is_pinned ?? false),
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'notes_note' => [
                ['field' => 'is_pinned', 'format' => 'boolean_pinned'],
                ['field' => 'is_done', 'format' => 'boolean_done'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'notes_note') {
            return [];
        }

        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        $notes = NotesNote::whereIn('id', $allIds)
            ->select('id', 'done', 'is_pinned')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = 0;
            $done = 0;
            $pinned = 0;
            $open = 0;

            foreach ($ids as $id) {
                $note = $notes[$id] ?? null;
                if (! $note) {
                    continue;
                }
                $total++;
                if ($note->done) {
                    $done++;
                } else {
                    $open++;
                }
                if ($note->is_pinned) {
                    $pinned++;
                }
            }

            $result[$entityId] = [
                'notes_total' => $total,
                'notes_open' => $open,
                'notes_done' => $done,
                'notes_pinned' => $pinned,
            ];
        }

        return $result;
    }

    public function metricDefinitions(): array
    {
        return [
            'notes_total'  => ['label' => 'Notizen (gesamt)', 'group' => 'notes', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'notes_open'   => ['label' => 'Notizen (offen)', 'group' => 'notes', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'complexity', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'notes_done'   => ['label' => 'Notizen (erledigt)', 'group' => 'notes', 'direction' => 'up', 'unit' => 'count', 'pair' => 'notes_total', 'dimension' => 'throughput', 'type' => 'flow', 'aggregation_mode' => 'rolled_up', 'basis' => 'cumulative_since_start'],
            'notes_pinned' => ['label' => 'Notizen (angepinnt)', 'group' => 'notes', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'potential', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
        ];
    }
}
