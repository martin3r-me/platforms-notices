<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;

class Dashboard extends Component
{
    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => 'Platform\Notes\Models\NotesFolder',
            'modelId' => null,
            'subject' => 'Notizen Dashboard',
            'description' => 'Übersicht aller Notizen und Ordner',
            'url' => route('notes.dashboard'),
            'source' => 'notes.dashboard',
            'recipients' => [],
            'meta' => [
                'view_type' => 'dashboard',
            ],
        ]);
    }

    public function createFolder()
    {
        $user = Auth::user();
        
        // Policy-Berechtigung prüfen
        $this->authorize('create', NotesFolder::class);

        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        // Neuen Ordner anlegen
        $folder = NotesFolder::create([
            'name' => 'Neuer Ordner',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->dispatch('updateSidebar');
        
        // Zur Ordner-Ansicht weiterleiten
        return $this->redirect(route('notes.folders.show', $folder), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        // === ORDNER (nur Team-Ordner, ohne Parent = Root-Ordner) ===
        $folders = NotesFolder::where('team_id', $team->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
        
        $activeFolders = $folders->filter(function($folder) {
            return $folder->done === null || $folder->done === false;
        })->count();
        $totalFolders = $folders->count();

        // === NOTIZEN (nur Team-Notizen ohne Ordner) ===
        $notes = NotesNote::where('team_id', $team->id)
            ->whereNull('folder_id')
            ->orderBy('name')
            ->get();
        
        $activeNotes = $notes->filter(function($note) {
            return $note->done === null || $note->done === false;
        })->count();
        $totalNotes = $notes->count();

        // === ORDNER-ÜBERSICHT (nur aktive Root-Ordner) ===
        $activeFoldersList = $folders->filter(function($folder) {
            return $folder->done === null || $folder->done === false;
        })
        ->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'subtitle' => $folder->description ? mb_substr($folder->description, 0, 50) . '...' : '',
            ];
        })
        ->take(5);

        // === NOTIZEN-ÜBERSICHT (nur aktive Notizen ohne Ordner) ===
        $activeNotesList = $notes->filter(function($note) {
            return $note->done === null || $note->done === false;
        })
        ->map(function ($note) {
            return [
                'id' => $note->id,
                'name' => $note->name,
                'subtitle' => $note->content ? mb_substr(strip_tags($note->content), 0, 50) . '...' : '',
            ];
        })
        ->take(5);

        return view('notes::livewire.dashboard', [
            'activeFolders' => $activeFolders,
            'totalFolders' => $totalFolders,
            'activeFoldersList' => $activeFoldersList,
            'activeNotes' => $activeNotes,
            'totalNotes' => $totalNotes,
            'activeNotesList' => $activeNotesList,
        ])->layout('platform::layouts.app');
    }
}
