<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllFolders = false;

    public function mount()
    {
        // Zustand aus localStorage laden (wird vom Frontend gesetzt)
        $this->showAllFolders = false; // Default-Wert, wird vom Frontend überschrieben
    }

    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        // Wird später implementiert
    }

    public function toggleShowAllFolders()
    {
        $this->showAllFolders = !$this->showAllFolders;
    }

    public function createFolder()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            return;
        }

        // Policy-Berechtigung prüfen
        $this->authorize('create', NotesFolder::class);

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

    public function getFolderTree()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return collect();
        }

        // Alle Root-Ordner laden
        $rootFolders = NotesFolder::where('team_id', $teamId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return $this->buildFolderTree($rootFolders, 0);
    }

    protected function buildFolderTree($folders, $level = 0, $maxLevel = 2)
    {
        if ($level > $maxLevel) {
            return collect();
        }

        $tree = collect();
        
        foreach ($folders as $folder) {
            $tree->push([
                'folder' => $folder,
                'level' => $level,
            ]);
            
            // Rekursiv Unterordner hinzufügen
            $children = $folder->children()->orderBy('name')->get();
            if ($children->count() > 0 && $level < $maxLevel) {
                $tree = $tree->merge($this->buildFolderTree($children, $level + 1, $maxLevel));
            }
        }
        
        return $tree;
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('notes::livewire.sidebar', [
                'folders' => collect(),
                'hasMoreFolders' => false,
                'allFoldersCount' => 0,
                'folderTree' => collect(),
            ]);
        }

        // Alle Root-Ordner des Teams (ohne Parent)
        $allFolders = NotesFolder::query()
            ->where('team_id', $teamId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Ordner filtern: alle oder nur bestimmte (später erweiterbar)
        $foldersToShow = $this->showAllFolders 
            ? $allFolders 
            : $allFolders; // Später: nur Ordner mit bestimmten Kriterien

        $hasMoreFolders = false; // Später: wenn Filter-Logik implementiert wird

        // Ordner-Baum für erweiterte Navigation
        $folderTree = $this->getFolderTree();

        return view('notes::livewire.sidebar', [
            'folders' => $foldersToShow,
            'hasMoreFolders' => $hasMoreFolders,
            'allFoldersCount' => $allFolders->count(),
            'folderTree' => $folderTree,
        ]);
    }
}
