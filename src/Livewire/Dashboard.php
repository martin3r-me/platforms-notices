<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;

class Dashboard extends Component
{
    public string $search = '';
    public string $viewMode = 'grid';
    public string $filterTag = '';
    public bool $showPinnedOnly = false;

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

        $this->authorize('create', NotesFolder::class);

        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $folder = NotesFolder::create([
            'name' => 'Neuer Ordner',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->dispatch('updateSidebar');

        return $this->redirect(route('notes.folders.show', $folder), navigate: true);
    }

    public function createQuickNote()
    {
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
        ]);

        return $this->redirect(route('notes.notes.show', $note), navigate: true);
    }

    public function togglePin($type, $id)
    {
        $user = Auth::user();

        if ($type === 'note') {
            $item = NotesNote::where('team_id', $user->currentTeam->id)->findOrFail($id);
            $this->authorize('update', $item);
            $item->update(['is_pinned' => !$item->is_pinned]);
        } else {
            $item = NotesFolder::where('team_id', $user->currentTeam->id)->findOrFail($id);
            $this->authorize('update', $item);
            $item->update(['is_pinned' => !$item->is_pinned]);
        }
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function setFilterTag($tag)
    {
        $this->filterTag = $this->filterTag === $tag ? '' : $tag;
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $foldersQuery = NotesFolder::where('team_id', $team->id)
            ->whereNull('parent_id')
            ->orderByDesc('is_pinned')
            ->orderBy('name');

        $notesQuery = NotesNote::where('team_id', $team->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at');

        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $notesQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('content', 'like', $searchTerm);
            });
            $foldersQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        if ($this->filterTag) {
            $tag = $this->filterTag;
            $notesQuery->whereJsonContains('tags', $tag);
            $foldersQuery->whereJsonContains('tags', $tag);
        }

        if ($this->showPinnedOnly) {
            $notesQuery->where('is_pinned', true);
            $foldersQuery->where('is_pinned', true);
        }

        $folders = $foldersQuery->get();
        $allNotes = $notesQuery->get();

        $totalFolders = NotesFolder::where('team_id', $team->id)->whereNull('parent_id')->count();
        $totalNotes = NotesNote::where('team_id', $team->id)->count();
        $activeFolders = $folders->where('done', false)->count();
        $activeNotes = $allNotes->where('done', false)->count();

        $pinnedNotes = $allNotes->where('is_pinned', true);
        $pinnedFolders = $folders->where('is_pinned', true);
        $recentNotes = $allNotes->where('is_pinned', false)->take(20);

        // Collect all tags
        $allTags = collect();
        $allNotes->each(function ($note) use ($allTags) {
            foreach ($note->tags ?? [] as $tag) {
                $allTags->push($tag);
            }
        });
        $folders->each(function ($folder) use ($allTags) {
            foreach ($folder->tags ?? [] as $tag) {
                $allTags->push($tag);
            }
        });
        $uniqueTags = $allTags->countBy()->sortDesc()->take(20);

        return view('notes::livewire.dashboard', [
            'activeFolders' => $activeFolders,
            'totalFolders' => $totalFolders,
            'activeNotes' => $activeNotes,
            'totalNotes' => $totalNotes,
            'folders' => $folders,
            'allNotes' => $allNotes,
            'pinnedNotes' => $pinnedNotes,
            'pinnedFolders' => $pinnedFolders,
            'recentNotes' => $recentNotes,
            'uniqueTags' => $uniqueTags,
        ])->layout('platform::layouts.app');
    }
}
