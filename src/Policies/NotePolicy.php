<?php

namespace Platform\Notes\Policies;

use Platform\Core\Models\User;
use Platform\Notes\Models\NotesNote;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Enums\FolderRole;

class NotePolicy
{
    /**
     * Darf der User diese Notiz sehen?
     */
    public function view(User $user, NotesNote $note): bool
    {
        // User muss im selben Team sein
        if ($note->team_id !== $user->currentTeam?->id) {
            return false;
        }

        // Wenn Notiz in einem Ordner ist, Ordner-Berechtigung prüfen
        if ($note->folder_id) {
            $folder = NotesFolder::find($note->folder_id);
            if ($folder) {
                return $user->can('view', $folder);
            }
        }

        return true;
    }

    /**
     * Darf der User diese Notiz bearbeiten?
     */
    public function update(User $user, NotesNote $note): bool
    {
        // User muss im selben Team sein
        if ($note->team_id !== $user->currentTeam?->id) {
            return false;
        }

        // Wenn Notiz in einem Ordner ist, Ordner-Schreibberechtigung prüfen
        if ($note->folder_id) {
            $folder = NotesFolder::find($note->folder_id);
            if ($folder) {
                // Viewer können nicht bearbeiten
                $userRole = $folder->getEffectiveRoleForUser($user->id);
                if ($userRole === FolderRole::VIEWER->value) {
                    return false;
                }
                // Owner hat immer Zugriff
                if ($folder->user_id === $user->id) {
                    return true;
                }
                // Andere Rollen: Schreibberechtigung prüfen
                return $user->can('update', $folder);
            }
        }

        return true;
    }

    /**
     * Darf der User diese Notiz löschen?
     */
    public function delete(User $user, NotesNote $note): bool
    {
        // Owner der Notiz kann immer löschen
        if ($note->user_id === $user->id) {
            return true;
        }

        // User muss im selben Team sein
        if ($note->team_id !== $user->currentTeam?->id) {
            return false;
        }

        // Wenn Notiz in einem Ordner ist, Ordner-Admin-Berechtigung prüfen
        if ($note->folder_id) {
            $folder = NotesFolder::find($note->folder_id);
            if ($folder) {
                // Viewer können nicht löschen
                $userRole = $folder->getEffectiveRoleForUser($user->id);
                if ($userRole === FolderRole::VIEWER->value) {
                    return false;
                }
                // Admin/Owner können löschen
                return in_array($userRole, [
                    FolderRole::OWNER->value,
                    FolderRole::ADMIN->value
                ], true);
            }
        }

        return true;
    }

    /**
     * Darf der User eine Notiz erstellen?
     */
    public function create(User $user, ?NotesFolder $folder = null): bool
    {
        // Jeder Team-Mitglied kann Notizen erstellen
        if (!$user->currentTeam) {
            return false;
        }

        // Wenn in einem Ordner, Ordner-Schreibberechtigung prüfen
        if ($folder) {
            // Viewer können keine Notizen erstellen
            $userRole = $folder->getEffectiveRoleForUser($user->id);
            if ($userRole === FolderRole::VIEWER->value) {
                return false;
            }
            return $user->can('update', $folder);
        }

        return true;
    }
}
