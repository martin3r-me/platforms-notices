<?php

namespace Platform\Notes\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Notes\Models\NotesNote;
use Livewire\Attributes\On;

class Note extends Component
{
    public NotesNote $note;
    public string $content = '';

    public function mount(NotesNote $notesNote)
    {
        $this->note = $notesNote;
        $this->content = $this->note->content ?? '';
        
        // Berechtigung prüfen
        $this->authorize('view', $this->note);
    }

    #[On('updateNote')] 
    public function updateNote()
    {
        $this->note->refresh();
        $this->content = $this->note->content ?? '';
    }

    public function save()
    {
        $this->authorize('update', $this->note);
        
        $this->note->update([
            'content' => $this->content,
        ]);

        session()->flash('message', 'Notiz gespeichert.');
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

        // Organization-Kontext setzen
        $this->dispatch('organization', [
            'context_type' => get_class($this->note),
            'context_id' => $this->note->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // KeyResult-Kontext setzen
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->note),
            'context_id' => $this->note->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('notes::livewire.note', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
