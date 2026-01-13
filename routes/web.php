<?php

use Platform\Notes\Livewire\Folder;
use Platform\Notes\Livewire\Note;
use Platform\Notes\Livewire\Dashboard;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;

Route::get('/', Dashboard::class)->name('notes.dashboard');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/folders/{notesFolder}', Folder::class)
    ->name('notes.folders.show');

Route::get('/notes/{notesNote}', Note::class)
    ->name('notes.notes.show');
