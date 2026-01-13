<?php

namespace Platform\Notes\Policies;

use Platform\Core\Models\User;
use Platform\Notes\Models\NotesFolder;

class FolderPolicy
{
    /**
     * Darf der User diesen Ordner sehen?
     */
    public function view(User $user, NotesFolder $folder): bool
    {
        // User muss im selben Team sein
        return $folder->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen Ordner bearbeiten?
     */
    public function update(User $user, NotesFolder $folder): bool
    {
        // User muss im selben Team sein
        return $folder->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User diesen Ordner löschen?
     */
    public function delete(User $user, NotesFolder $folder): bool
    {
        // Nur Team-Mitglied im selben Team darf löschen
        return $folder->team_id === $user->currentTeam?->id;
    }

    /**
     * Darf der User einen Ordner erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Ordner erstellen
        return $user->currentTeam !== null;
    }
}
