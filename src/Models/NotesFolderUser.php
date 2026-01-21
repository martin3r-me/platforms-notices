<?php

namespace Platform\Notes\Models;

use Illuminate\Database\Eloquent\Model;

class NotesFolderUser extends Model
{
    protected $table = 'notes_folder_users';

    protected $fillable = ['folder_id', 'role', 'user_id'];

    public function user()
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function folder()
    {
        return $this->belongsTo(NotesFolder::class, 'folder_id');
    }
}
