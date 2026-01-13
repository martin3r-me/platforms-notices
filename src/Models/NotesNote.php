<?php

namespace Platform\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Organization\Traits\HasOrganizationContexts;
use Platform\Core\Contracts\HasTimeAncestors;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\HasDisplayName;

/**
 * @ai.description Notiz mit Markdown-Inhalt.
 */
class NotesNote extends Model implements HasTimeAncestors, HasKeyResultAncestors, HasDisplayName
{
    use HasOrganizationContexts;

    protected $table = 'notes_notes';

    protected $fillable = [
        'uuid',
        'name',
        'content',
        'order',
        'folder_id',
        'user_id',
        'team_id',
        'done',
        'done_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'done' => 'boolean',
        'done_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(NotesFolder::class, 'folder_id');
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die Zeitkaskade zurück.
     */
    public function timeAncestors(): array
    {
        return [];
    }

    /**
     * Gibt alle Vorfahren-Kontexte für die KeyResult-Kaskade zurück.
     */
    public function keyResultAncestors(): array
    {
        return [];
    }

    /**
     * Gibt den anzeigbaren Namen der Notiz zurück.
     */
    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
