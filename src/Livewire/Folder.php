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

        // Owner automatisch als folderUser hinzufügen
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

    public function updateFolderName($name = null)
    {
        $this->authorize('update', $this->folder);
        
        // Wenn kein Name übergeben, aus dem Model nehmen
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

    public function addFolderUser($userId = null, $role = null)
    {
        $this->authorize('invite', $this->folder);
        
        // Wenn keine Parameter, aus Properties nehmen
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

        // Prüfen, ob User im Team ist
        $targetUser = \Platform\Core\Models\User::find($userId);
        if (!$targetUser || !$team->users()->where('users.id', $userId)->exists()) {
            session()->flash('error', 'Der ausgewählte User ist kein Mitglied des Teams.');
            return;
        }

        // Rolle validieren
        $allowedRoles = [
            FolderRole::ADMIN->value,
            FolderRole::MEMBER->value,
            FolderRole::VIEWER->value
        ];
        if (!in_array($role, $allowedRoles, true)) {
            session()->flash('error', 'Ungültige Rolle.');
            return;
        }

        // Prüfen, ob bereits vorhanden
        $existing = NotesFolderUser::where('folder_id', $this->folder->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            // Rolle aktualisieren
            $existing->update(['role' => $role]);
            session()->flash('success', 'Rolle wurde aktualisiert.');
        } else {
            // Neuen Eintrag erstellen
            NotesFolderUser::create([
                'folder_id' => $this->folder->id,
                'user_id' => $userId,
                'role' => $role,
            ]);
            session()->flash('success', 'User wurde zum Ordner hinzugefügt.');
        }

        // Reset
        $this->selectedUserId = null;
        $this->selectedRole = 'viewer';
        $this->folder->refresh();
    }

    public function removeFolderUser($userId)
    {
        $this->authorize('removeMember', $this->folder);
        
        // Owner kann nicht entfernt werden
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

        // Owner-Rolle kann nicht geändert werden (außer durch Ownership-Transfer)
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
        $team = $user->currentTeam;
        
        // Unterordner und Notizen für diesen Ordner laden
        $subFolders = $this->folder->children;
        $notes = $this->folder->notes;

        // Ordner-User laden
        $folderUsers = $this->folder->folderUsers()->with('user')->get();
        
        // Owner hinzufügen, falls nicht bereits in folderUsers
        if ($this->folder->user_id && !$folderUsers->contains('user_id', $this->folder->user_id)) {
            $ownerUser = \Platform\Core\Models\User::find($this->folder->user_id);
            if ($ownerUser) {
                // Temporäres Objekt für Owner erstellen
                $ownerFolderUser = new NotesFolderUser([
                    'folder_id' => $this->folder->id,
                    'user_id' => $ownerUser->id,
                    'role' => FolderRole::OWNER->value,
                ]);
                $ownerFolderUser->setRelation('user', $ownerUser);
                $folderUsers->prepend($ownerFolderUser);
            }
        }
        
        // Team-User für Auswahl laden
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
