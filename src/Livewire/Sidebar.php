<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllFolders = false;
    public array $expandedFolders = [];

    public function mount()
    {
        // Zustand aus localStorage laden (wird vom Frontend gesetzt)
        $this->showAllFolders = false; // Default-Wert, wird vom Frontend überschrieben
        
        // Standardmäßig alle Ordner als erweitert markieren
        $this->initializeExpandedFolders();
    }

    protected function initializeExpandedFolders()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return;
        }

        // Alle Ordner laden, auf die der User Zugriff hat
        $allFolders = NotesFolder::where('team_id', $teamId)
            ->get()
            ->filter(function ($folder) use ($user) {
                return $user->can('view', $folder);
            });

        // Alle Ordner-IDs als erweitert markieren
        $this->expandedFolders = $allFolders->pluck('id')->toArray();
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

        // Owner automatisch als folderUser hinzufügen
        $folder->folderUsers()->create([
            'user_id' => $user->id,
            'role' => \Platform\Notes\Enums\FolderRole::OWNER->value,
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

        // Alle Root-Ordner des Teams laden
        $allRootFolders = NotesFolder::where('team_id', $teamId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Nur Ordner filtern, auf die der User Zugriff hat
        $rootFolders = $allRootFolders->filter(function ($folder) use ($user) {
            return $user->can('view', $folder);
        });

        return $this->buildFolderTree($rootFolders, 0);
    }

    public function toggleFolder($folderId)
    {
        if (in_array($folderId, $this->expandedFolders)) {
            $this->expandedFolders = array_values(array_filter($this->expandedFolders, fn($id) => $id !== $folderId));
        } else {
            $this->expandedFolders[] = $folderId;
        }
        
        // In localStorage speichern (wird vom Frontend übernommen)
    }

    public function isFolderExpanded($folderId): bool
    {
        return in_array($folderId, $this->expandedFolders);
    }

    protected function buildFolderTree($folders, $level = 0, $maxLevel = 10, $parentExpanded = true)
    {
        if ($level > $maxLevel) {
            return collect();
        }

        $user = auth()->user();
        $tree = collect();
        
        foreach ($folders as $folder) {
            // Nur Ordner hinzufügen, auf die der User Zugriff hat
            if (!$user->can('view', $folder)) {
                continue;
            }

            // Prüfen ob Parent-Ordner erweitert ist (außer bei Root-Level)
            if ($level > 0 && !$parentExpanded) {
                continue;
            }

            $hasChildren = $folder->children()->exists();
            $isExpanded = $this->isFolderExpanded($folder->id);

            $tree->push([
                'folder' => $folder,
                'level' => $level,
                'hasChildren' => $hasChildren,
                'isExpanded' => $isExpanded,
            ]);
            
            // Rekursiv Unterordner hinzufügen (nur wenn erweitert)
            if ($hasChildren && $isExpanded && $level < $maxLevel) {
                $children = $folder->children()->orderBy('name')->get();
                $tree = $tree->merge($this->buildFolderTree($children, $level + 1, $maxLevel, $isExpanded));
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

        // Ordner filtern: nur solche, auf die der User Zugriff hat
        $foldersToShow = $allFolders->filter(function ($folder) use ($user) {
            return $user->can('view', $folder);
        });

        $hasMoreFolders = false; // Später: wenn Filter-Logik implementiert wird

        // Ordner-Baum für erweiterte Navigation
        $folderTree = $this->getFolderTree();

        // Root-Ordner mit Children-Info laden für die Sidebar
        $rootFoldersWithChildren = NotesFolder::query()
            ->where('team_id', $teamId)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get()
            ->filter(function ($folder) use ($user) {
                return $user->can('view', $folder);
            });

        return view('notes::livewire.sidebar', [
            'folders' => $foldersToShow,
            'hasMoreFolders' => $hasMoreFolders,
            'allFoldersCount' => $allFolders->count(),
            'folderTree' => $folderTree,
            'rootFolders' => $rootFoldersWithChildren,
        ]);
    }
}
