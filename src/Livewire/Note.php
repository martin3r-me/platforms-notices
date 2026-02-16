<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesNote;
use Platform\Notes\Models\NotesNoteShare;
use Livewire\Attributes\On;

class Note extends Component
{
    public NotesNote $note;
    public string $content = '';
    public string $name = '';
    public $shareUserId = null;

    public function mount(NotesNote $notesNote)
    {
        $this->note = $notesNote;
        $this->content = $this->note->content ?? '';
        $this->name = $this->note->name;

        $this->authorize('view', $this->note);
    }

    #[On('updateNote')]
    public function updateNote()
    {
        $this->note->refresh();
        $this->content = $this->note->content ?? '';
        $this->name = $this->note->name;

        $this->dispatch('notes-sync-editor', [
            'noteId' => $this->note->id,
            'name' => $this->name,
            'content' => $this->content,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->note);

        $this->note->update([
            'name' => $this->name,
            'content' => $this->content,
        ]);
        $this->note->refresh();

        $this->dispatch('notes-saved', [
            'noteId' => $this->note->id,
            'savedAt' => now()->toIso8601String(),
        ]);
    }

    public function togglePin()
    {
        $this->authorize('update', $this->note);
        $this->note->update(['is_pinned' => !$this->note->is_pinned]);
        $this->note->refresh();
    }

    public function addTag($tag)
    {
        $this->authorize('update', $this->note);

        $tag = trim(str_replace(['#', ',', ' '], ['', '', '-'], $tag));
        if (empty($tag)) return;

        $tags = $this->note->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->note->update(['tags' => $tags]);
            $this->note->refresh();
        }
    }

    public function removeTag($tag)
    {
        $this->authorize('update', $this->note);

        $tags = $this->note->tags ?? [];
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        $this->note->update(['tags' => $tags]);
        $this->note->refresh();
    }

    public function addShare()
    {
        $this->authorize('update', $this->note);

        if (!$this->shareUserId) return;

        $user = Auth::user();
        $team = $user->currentTeam;

        $targetUser = \Platform\Core\Models\User::find($this->shareUserId);
        if (!$targetUser || !$team->users()->where('users.id', $this->shareUserId)->exists()) {
            session()->flash('error', 'User ist kein Mitglied des Teams.');
            return;
        }

        NotesNoteShare::updateOrCreate(
            ['note_id' => $this->note->id, 'user_id' => $this->shareUserId],
            ['permission' => 'view']
        );

        $this->shareUserId = null;
        $this->note->refresh();
    }

    public function updateSharePermission($userId, $permission)
    {
        $this->authorize('update', $this->note);

        if (!in_array($permission, ['view', 'edit'])) return;

        NotesNoteShare::where('note_id', $this->note->id)
            ->where('user_id', $userId)
            ->update(['permission' => $permission]);
    }

    public function removeShare($userId)
    {
        $this->authorize('update', $this->note);

        NotesNoteShare::where('note_id', $this->note->id)
            ->where('user_id', $userId)
            ->delete();

        $this->note->refresh();
    }

    public function deleteNote()
    {
        $this->authorize('delete', $this->note);

        $folderId = $this->note->folder_id;
        $this->note->delete();

        $this->dispatch('updateSidebar');

        if ($folderId) {
            return $this->redirect(route('notes.folders.show', $folderId), navigate: true);
        }

        return $this->redirect(route('notes.dashboard'), navigate: true);
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => route('notes.dashboard')],
        ];

        if ($this->note->folder) {
            $folder = $this->note->folder;
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
        }

        $breadcrumbs[] = [
            'name' => $this->note->name,
            'url' => route('notes.notes.show', $this->note),
        ];

        return $breadcrumbs;
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->note),
            'modelId' => $this->note->id,
            'subject' => $this->note->name,
            'description' => mb_substr(strip_tags($this->note->content ?? ''), 0, 100),
            'url' => route('notes.notes.show', $this->note),
            'source' => 'notes.note.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->note->created_at,
            ],
        ]);

        $this->dispatch('organization', [
            'context_type' => get_class($this->note),
            'context_id' => $this->note->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        $this->dispatch('keyresult', [
            'context_type' => get_class($this->note),
            'context_id' => $this->note->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $shares = $this->note->shares()->with('user')->get();
        $teamUsers = $team ? $team->users()->orderBy('name')->get() : collect();

        return view('notes::livewire.note', [
            'user' => $user,
            'shares' => $shares,
            'teamUsers' => $teamUsers,
        ])->layout('platform::layouts.app');
    }
}
