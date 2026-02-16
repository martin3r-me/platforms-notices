<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;
use Platform\Notes\Models\NotesFolderUser;
use Platform\Notes\Enums\FolderRole;
use Livewire\Attributes\On;

class Folder extends Component
{
    public NotesFolder $folder;
    public $selectedUserId = null;
    public $selectedRole = 'viewer';
    public string $viewMode = 'grid';
    public string $search = '';

    public function mount(NotesFolder $notesFolder)
    {
        $this->folder = $notesFolder;

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

        $subFolder->folderUsers()->create([
            'user_id' => $user->id,
            'role' => FolderRole::OWNER->value,
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

    public function togglePin($type, $id)
    {
        if ($type === 'note') {
            $item = NotesNote::findOrFail($id);
            $this->authorize('update', $item);
            $item->update(['is_pinned' => !$item->is_pinned]);
        } elseif ($type === 'folder') {
            $item = NotesFolder::findOrFail($id);
            $this->authorize('update', $item);
            $item->update(['is_pinned' => !$item->is_pinned]);
        }
        $this->folder->refresh();
    }

    public function toggleFolderPin()
    {
        $this->authorize('update', $this->folder);
        $this->folder->update(['is_pinned' => !$this->folder->is_pinned]);
        $this->folder->refresh();
    }

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function deleteFolder()
    {
        $this->authorize('delete', $this->folder);

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

    public function updateFolderName($name = null)
    {
        $this->authorize('update', $this->folder);

        if ($name === null) {
            $name = $this->folder->name;
        }

        $name = trim($name);
        if (empty($name)) {
            session()->flash('error', 'Der Ordner-Name darf nicht leer sein.');
            $this->folder->refresh();
            return;
        }

        $this->folder->update(['name' => $name]);
        $this->folder->refresh();

        session()->flash('success', 'Ordner wurde umbenannt.');
    }

    public function addFolderTag($tag)
    {
        $this->authorize('update', $this->folder);

        $tag = trim(str_replace(['#', ',', ' '], ['', '', '-'], $tag));
        if (empty($tag)) return;

        $tags = $this->folder->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->folder->update(['tags' => $tags]);
            $this->folder->refresh();
        }
    }

    public function removeFolderTag($tag)
    {
        $this->authorize('update', $this->folder);

        $tags = $this->folder->tags ?? [];
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        $this->folder->update(['tags' => $tags]);
        $this->folder->refresh();
    }

    public function addFolderUser($userId = null, $role = null)
    {
        $this->authorize('invite', $this->folder);

        if ($userId === null) {
            $userId = $this->selectedUserId;
        }
        if ($role === null) {
            $role = $this->selectedRole;
        }

        if (!$userId) {
            session()->flash('error', 'Bitte wählen Sie einen User aus.');
            return;
        }

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $targetUser = \Platform\Core\Models\User::find($userId);
        if (!$targetUser || !$team->users()->where('users.id', $userId)->exists()) {
            session()->flash('error', 'Der ausgewählte User ist kein Mitglied des Teams.');
            return;
        }

        $allowedRoles = [
            FolderRole::ADMIN->value,
            FolderRole::MEMBER->value,
            FolderRole::VIEWER->value
        ];
        if (!in_array($role, $allowedRoles, true)) {
            session()->flash('error', 'Ungültige Rolle.');
            return;
        }

        $existing = NotesFolderUser::where('folder_id', $this->folder->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->update(['role' => $role]);
            session()->flash('success', 'Rolle wurde aktualisiert.');
        } else {
            NotesFolderUser::create([
                'folder_id' => $this->folder->id,
                'user_id' => $userId,
                'role' => $role,
            ]);
            session()->flash('success', 'User wurde zum Ordner hinzugefügt.');
        }

        $this->selectedUserId = null;
        $this->selectedRole = 'viewer';
        $this->folder->refresh();
    }

    public function removeFolderUser($userId)
    {
        $this->authorize('removeMember', $this->folder);

        $folderUser = NotesFolderUser::where('folder_id', $this->folder->id)
            ->where('user_id', $userId)
            ->first();

        if (!$folderUser) {
            session()->flash('error', 'User nicht gefunden.');
            return;
        }

        if ($folderUser->role === FolderRole::OWNER->value) {
            session()->flash('error', 'Der Owner kann nicht entfernt werden.');
            return;
        }

        $folderUser->delete();
        $this->folder->refresh();

        session()->flash('success', 'User wurde aus dem Ordner entfernt.');
    }

    public function changeFolderUserRole($userId, $newRole)
    {
        $this->authorize('changeRole', $this->folder);

        $folderUser = NotesFolderUser::where('folder_id', $this->folder->id)
            ->where('user_id', $userId)
            ->first();

        if (!$folderUser) {
            session()->flash('error', 'User nicht gefunden.');
            return;
        }

        if ($folderUser->role === FolderRole::OWNER->value && $newRole !== FolderRole::OWNER->value) {
            session()->flash('error', 'Die Owner-Rolle kann nicht geändert werden.');
            return;
        }

        $folderUser->update(['role' => $newRole]);
        $this->folder->refresh();

        session()->flash('success', 'Rolle wurde geändert.');
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

        $this->dispatch('organization', [
            'context_type' => get_class($this->folder),
            'context_id' => $this->folder->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

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
        $team = $user->currentTeam;

        $subFoldersQuery = $this->folder->children()->orderByDesc('is_pinned')->orderBy('name');
        $notesQuery = $this->folder->notes()->orderByDesc('is_pinned')->orderByDesc('updated_at');

        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $notesQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('content', 'like', $searchTerm);
            });
            $subFoldersQuery->where('name', 'like', $searchTerm);
        }

        $subFolders = $subFoldersQuery->get();
        $notes = $notesQuery->get();

        $folderUsers = $this->folder->folderUsers()->with('user')->get();

        if ($this->folder->user_id && !$folderUsers->contains('user_id', $this->folder->user_id)) {
            $ownerUser = \Platform\Core\Models\User::find($this->folder->user_id);
            if ($ownerUser) {
                $ownerFolderUser = new NotesFolderUser([
                    'folder_id' => $this->folder->id,
                    'user_id' => $ownerUser->id,
                    'role' => FolderRole::OWNER->value,
                ]);
                $ownerFolderUser->setRelation('user', $ownerUser);
                $folderUsers->prepend($ownerFolderUser);
            }
        }

        $teamUsers = $team ? $team->users()->orderBy('name')->get() : collect();

        return view('notes::livewire.folder', [
            'user' => $user,
            'subFolders' => $subFolders,
            'notes' => $notes,
            'folderUsers' => $folderUsers,
            'teamUsers' => $teamUsers,
        ])->layout('platform::layouts.app');
    }
}
