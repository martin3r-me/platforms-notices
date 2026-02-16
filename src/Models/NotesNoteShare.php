<?php

namespace Platform\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotesNoteShare extends Model
{
    protected $table = 'notes_note_shares';

    protected $fillable = [
        'note_id',
        'user_id',
        'permission',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(NotesNote::class, 'note_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }
}
