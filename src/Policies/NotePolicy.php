<?php

namespace Platform\Notes\Policies;

use Platform\Core\Models\User;
use Platform\Notes\Models\NotesNote;

class NotePolicy
{
    /**
     * Darf der User diese Notiz sehen?
     */
    public function view(User $user, NotesNote $note): bool
    {
        // User muss im selben Team sein
        return $note->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Notiz bearbeiten?
     */
    public function update(User $user, NotesNote $note): bool
    {
        // User muss im selben Team sein
        return $note->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diese Notiz löschen?
     */
    public function delete(User $user, NotesNote $note): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $note->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User eine Notiz erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Notizen erstellen
        return $user->currentTeam !== null;
    }
}
