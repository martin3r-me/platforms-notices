<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllFolders = false;
    public array $expandedFolders = [];
    public string $sidebarSearch = '';

    public function mount()
    {
        $this->showAllFolders = false;
        $this->initializeExpandedFolders();
    }

    protected function initializeExpandedFolders()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return;
        }

        $allFolders = NotesFolder::where('team_id', $teamId)
            ->get()
            ->filter(function ($folder) use ($user) {
                return $user->can('view', $folder);
            });

        $this->expandedFolders = $allFolders->pluck('id')->toArray();
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {
        // Re-render triggers fresh data
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

        $this->authorize('create', NotesFolder::class);

        $folder = NotesFolder::create([
            'name' => 'Neuer Ordner',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $folder->folderUsers()->create([
            'user_id' => $user->id,
            'role' => \Platform\Notes\Enums\FolderRole::OWNER->value,
        ]);

        $this->dispatch('updateSidebar');

        return $this->redirect(route('notes.folders.show', $folder), navigate: true);
    }

    public function createQuickNote()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            return;
        }

        $note = NotesNote::create([
            'name' => 'Neue Notiz',
            'content' => '',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        return $this->redirect(route('notes.notes.show', $note), navigate: true);
    }

    public function getFolderTree()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return collect();
        }

        $allRootFolders = NotesFolder::where('team_id', $teamId)
            ->whereNull('parent_id')
            ->orderByDesc('is_pinned')
            ->orderBy('name')
            ->get();

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
            if (!$user->can('view', $folder)) {
                continue;
            }

            if ($level > 0 && !$parentExpanded) {
                continue;
            }

            // Filter by search
            if ($this->sidebarSearch) {
                $matchesSearch = str_contains(mb_strtolower($folder->name), mb_strtolower($this->sidebarSearch));
                $hasMatchingChild = $this->hasMatchingChild($folder, $this->sidebarSearch);
                if (!$matchesSearch && !$hasMatchingChild) {
                    continue;
                }
            }

            $hasChildren = $folder->children()->exists();
            $isExpanded = $this->isFolderExpanded($folder->id);

            $tree->push([
                'folder' => $folder,
                'level' => $level,
                'hasChildren' => $hasChildren,
                'isExpanded' => $isExpanded,
            ]);

            if ($hasChildren && $isExpanded && $level < $maxLevel) {
                $children = $folder->children()->orderByDesc('is_pinned')->orderBy('name')->get();
                $tree = $tree->merge($this->buildFolderTree($children, $level + 1, $maxLevel, $isExpanded));
            }
        }

        return $tree;
    }

    protected function hasMatchingChild($folder, $search): bool
    {
        $search = mb_strtolower($search);
        foreach ($folder->children as $child) {
            if (str_contains(mb_strtolower($child->name), $search)) {
                return true;
            }
            if ($this->hasMatchingChild($child, $search)) {
                return true;
            }
        }
        return false;
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
                'pinnedNotes' => collect(),
                'recentNotes' => collect(),
            ]);
        }

        $allFolders = NotesFolder::query()
            ->where('team_id', $teamId)
            ->whereNull('parent_id')
            ->orderByDesc('is_pinned')
            ->orderBy('name')
            ->get();

        $foldersToShow = $allFolders->filter(function ($folder) use ($user) {
            return $user->can('view', $folder);
        });

        $hasMoreFolders = false;
        $folderTree = $this->getFolderTree();

        $rootFoldersWithChildren = NotesFolder::query()
            ->where('team_id', $teamId)
            ->whereNull('parent_id')
            ->with('children')
            ->orderByDesc('is_pinned')
            ->orderBy('name')
            ->get()
            ->filter(function ($folder) use ($user) {
                return $user->can('view', $folder);
            });

        // Pinned notes for quick access
        $pinnedNotes = NotesNote::where('team_id', $teamId)
            ->where('is_pinned', true)
            ->orderBy('name')
            ->take(10)
            ->get();

        // Recent notes
        $recentNotes = NotesNote::where('team_id', $teamId)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        return view('notes::livewire.sidebar', [
            'folders' => $foldersToShow,
            'hasMoreFolders' => $hasMoreFolders,
            'allFoldersCount' => $allFolders->count(),
            'folderTree' => $folderTree,
            'rootFolders' => $rootFoldersWithChildren,
            'pinnedNotes' => $pinnedNotes,
            'recentNotes' => $recentNotes,
        ]);
    }
}
