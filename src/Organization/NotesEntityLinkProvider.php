<?php

namespace Platform\Notes\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;

class NotesEntityLinkProvider implements EntityLinkProvider
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
        return [];
    }
}
