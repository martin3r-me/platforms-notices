<?php

namespace Platform\Notes\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Platform\Notes\Models\NotesNote;
use Barryvdh\DomPDF\Facade\Pdf;

class NotePdfController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(NotesNote $notesNote)
    {
        $this->authorize('view', $notesNote);

        $html = view('notes::pdf.note', [
            'note' => $notesNote,
        ])->render();

        $filename = str($notesNote->name ?: 'notiz')
            ->slug('-')
            ->append('.pdf')
            ->toString();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->download($filename);
    }
}

