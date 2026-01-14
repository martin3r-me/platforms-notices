<?php

namespace Platform\Notes\Tools;

/**
 * Alias für UpdateNoteTool, damit LLMs auch PUT verwenden können,
 * ohne aus Versehen den Content zu überschreiben.
 */
class PutNoteTool extends UpdateNoteTool
{
    public function getName(): string
    {
        return 'notes.notes.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /notes/notes/{id} - Aktualisiert eine Notiz (partial update). Parameter: id (required), name (optional), content (optional markdown). NULL-Werte werden ignoriert.';
    }
}

