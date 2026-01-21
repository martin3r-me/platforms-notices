<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;
use Livewire\Attributes\On;

class Folder extends Component
{
    public NotesFolder $folder;

    public function mount(NotesFolder $notesFolder)
    {
        $this->folder = $notesFolder;
        
        // Berechtigung prüfen
        $this->authorize('view', $this->folder);
    }

    #[On('updateFolder')] 
    public function updateFolder()
    {
        $this->folder->refresh();
    }

    public function createSubFolder()
    {
        $this->authorize('update', $this->folder);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $subFolder = NotesFolder::create([
            'name' => 'Neuer Unterordner',
            'user_id' => $user->id,
            'team_id' => $team->id,
            'parent_id' => $this->folder->id,
        ]);

        $this->folder->refresh();
        $this->dispatch('updateSidebar');
        
        return $this->redirect(route('notes.folders.show', $subFolder), navigate: true);
    }

    public function createNote()
    {
        $this->authorize('update', $this->folder);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $note = NotesNote::create([
            'name' => 'Neue Notiz',
            'content' => '',
            'user_id' => $user->id,
            'team_id' => $team->id,
            'folder_id' => $this->folder->id,
        ]);

        $this->folder->refresh();
        
        return $this->redirect(route('notes.notes.show', $note), navigate: true);
    }

    public function deleteFolder()
    {
        $this->authorize('delete', $this->folder);
        
        // Prüfen, ob Unterordner oder Notizen vorhanden sind
        if ($this->folder->children()->count() > 0 || $this->folder->notes()->count() > 0) {
            session()->flash('error', 'Der Ordner kann nicht gelöscht werden, da er noch Unterordner oder Notizen enthält.');
            return;
        }

        $parentId = $this->folder->parent_id;
        $this->folder->delete();

        $this->dispatch('updateSidebar');

        if ($parentId) {
            return $this->redirect(route('notes.folders.show', $parentId), navigate: true);
        }

        return $this->redirect(route('notes.dashboard'), navigate: true);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->folder),
            'modelId' => $this->folder->id,
            'subject' => $this->folder->name,
            'description' => $this->folder->description ?? '',
            'url' => route('notes.folders.show', $this->folder),
            'source' => 'notes.folder.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->folder->created_at,
            ],
        ]);

        // Organization-Kontext setzen
        $this->dispatch('organization', [
            'context_type' => get_class($this->folder),
            'context_id' => $this->folder->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // KeyResult-Kontext setzen
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->folder),
            'context_id' => $this->folder->id,
        ]);
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => route('notes.dashboard')],
        ];

        $folder = $this->folder;
        $path = [];
        
        // Pfad zum Root sammeln
        while ($folder) {
            array_unshift($path, $folder);
            $folder = $folder->parent;
        }
        
        foreach ($path as $f) {
            $breadcrumbs[] = [
                'name' => $f->name,
                'url' => route('notes.folders.show', $f),
            ];
        }

        return $breadcrumbs;
    }

    public function getFolderTree()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            return collect();
        }

        // Alle Root-Ordner laden
        $rootFolders = NotesFolder::where('team_id', $team->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return $this->buildFolderTree($rootFolders, $this->folder->id);
    }

    protected function buildFolderTree($folders, $activeFolderId, $level = 0)
    {
        $tree = collect();
        
        foreach ($folders as $folder) {
            $tree->push([
                'folder' => $folder,
                'level' => $level,
                'isActive' => $folder->id === $activeFolderId,
                'hasChildren' => $folder->children()->count() > 0,
            ]);
            
            // Rekursiv Unterordner hinzufügen, wenn aktiv oder wenn Unterordner aktiv ist
            if ($folder->id === $activeFolderId || $this->hasActiveChild($folder, $activeFolderId)) {
                $children = $folder->children()->orderBy('name')->get();
                $tree = $tree->merge($this->buildFolderTree($children, $activeFolderId, $level + 1));
            }
        }
        
        return $tree;
    }

    protected function hasActiveChild($folder, $activeFolderId)
    {
        foreach ($folder->children as $child) {
            if ($child->id === $activeFolderId) {
                return true;
            }
            if ($this->hasActiveChild($child, $activeFolderId)) {
                return true;
            }
        }
        return false;
    }

    public function render()
    {
        $user = Auth::user();
        
        // Unterordner und Notizen für diesen Ordner laden
        $subFolders = $this->folder->children;
        $notes = $this->folder->notes;

        return view('notes::livewire.folder', [
            'user' => $user,
            'subFolders' => $subFolders,
            'notes' => $notes,
        ])->layout('platform::layouts.app');
    }
}
