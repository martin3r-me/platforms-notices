<?php

namespace Platform\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Organization\Traits\HasOrganizationContexts;
use Platform\Core\Contracts\HasTimeAncestors;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\HasDisplayName;

/**
 * @ai.description Ordner für Notizen mit Unterstützung für Unterordner.
 */
class NotesFolder extends Model implements HasTimeAncestors, HasKeyResultAncestors, HasDisplayName
{
    use HasOrganizationContexts;

    protected $table = 'notes_folders';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'order',
        'parent_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NotesFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NotesFolder::class, 'parent_id')->orderBy('order');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(NotesNote::class, 'folder_id')->orderBy('order');
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
     * Gibt den anzeigbaren Namen des Ordners zurück.
     */
    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
