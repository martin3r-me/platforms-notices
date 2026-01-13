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
    public string $name = '';

    public function mount(NotesNote $notesNote)
    {
        $this->note = $notesNote;
        $this->content = $this->note->content ?? '';
        $this->name = $this->note->name;
        
        // Berechtigung prüfen
        $this->authorize('view', $this->note);
    }

    #[On('updateNote')] 
    public function updateNote()
    {
        $this->note->refresh();
        $this->content = $this->note->content ?? '';
        $this->name = $this->note->name;
    }

    public function updatedContent()
    {
        $this->save(true);
    }

    public function updatedName()
    {
        $this->save(true);
    }

    public function save($silent = false)
    {
        $this->authorize('update', $this->note);
        
        $this->note->update([
            'name' => $this->name,
            'content' => $this->content,
        ]);

        if (!$silent) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Gespeichert',
            ]);
        }
    }

    public function getBreadcrumbs()
    {
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => route('notes.dashboard')],
        ];

        if ($this->note->folder) {
            $folder = $this->note->folder;
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
