<?php

namespace Platform\Notes\Policies;

use Platform\Core\Policies\RolePolicy;
use Platform\Core\Models\User;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Enums\FolderRole;

class FolderPolicy extends RolePolicy
{
    /**
     * Darf der User diesen Ordner sehen?
     */
    public function view(User $user, $folder): bool
    {
        // 1. Owner hat immer Zugriff
        if ($this->isOwner($user, $folder)) {
            return true;
        }

        // 2. Team-Mitgliedschaft prüfen
        if (!$this->isInTeam($user, $folder)) {
            return false;
        }

        // 3. Effektive Rolle prüfen (inkl. Vererbung)
        $userRole = $this->getUserFolderRole($user, $folder);
        if ($userRole !== null) {
            return true; // Jede Rolle (owner, admin, member, viewer) kann sehen
        }

        return false;
    }

    /**
     * Darf der User diesen Ordner bearbeiten?
     */
    public function update(User $user, $folder): bool
    {
        // 1. Owner hat immer Zugriff
        if ($this->isOwner($user, $folder)) {
            return true;
        }

        // 2. Team-Mitgliedschaft prüfen
        if (!$this->isInTeam($user, $folder)) {
            return false;
        }

        // 3. Schreibrolle prüfen (admin, member)
        $userRole = $this->getUserFolderRole($user, $folder);
        return in_array($userRole, [
            FolderRole::OWNER->value,
            FolderRole::ADMIN->value,
            FolderRole::MEMBER->value
        ], true);
    }

    /**
     * Darf der User diesen Ordner löschen?
     */
    public function delete(User $user, $folder): bool
    {
        // Nur Owner darf löschen
        if ($this->isOwner($user, $folder)) {
            return true;
        }

        // Team-Mitgliedschaft prüfen
        if (!$this->isInTeam($user, $folder)) {
            return false;
        }

        // Nur Owner-Rolle darf löschen
        $userRole = $this->getUserFolderRole($user, $folder);
        return $userRole === FolderRole::OWNER->value;
    }

    /**
     * Darf der User einen Ordner erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder Team-Mitglied kann Ordner erstellen
        return $user->currentTeam !== null;
    }

    /**
     * Darf der User Mitglieder einladen?
     */
    public function invite(User $user, $folder): bool
    {
        // Nur Owner und Admin können einladen
        if ($this->isOwner($user, $folder)) {
            return true;
        }

        if (!$this->isInTeam($user, $folder)) {
            return false;
        }

        $userRole = $this->getUserFolderRole($user, $folder);
        return in_array($userRole, [
            FolderRole::OWNER->value,
            FolderRole::ADMIN->value
        ], true);
    }

    /**
     * Darf der User Mitglieder entfernen?
     */
    public function removeMember(User $user, $folder): bool
    {
        // Nur Owner und Admin können entfernen
        if ($this->isOwner($user, $folder)) {
            return true;
        }

        if (!$this->isInTeam($user, $folder)) {
            return false;
        }

        $userRole = $this->getUserFolderRole($user, $folder);
        return in_array($userRole, [
            FolderRole::OWNER->value,
            FolderRole::ADMIN->value
        ], true);
    }

    /**
     * Darf der User Rollen ändern?
     */
    public function changeRole(User $user, $folder): bool
    {
        // Nur Owner kann Rollen ändern
        if ($this->isOwner($user, $folder)) {
            return true;
        }

        if (!$this->isInTeam($user, $folder)) {
            return false;
        }

        $userRole = $this->getUserFolderRole($user, $folder);
        return $userRole === FolderRole::OWNER->value;
    }

    /**
     * Hole die Ordner-Rolle des Users (inkl. Vererbung)
     */
    protected function getUserFolderRole(User $user, $folder): ?string
    {
        if (!$folder instanceof NotesFolder) {
            return null;
        }
        
        return $folder->getEffectiveRoleForUser($user->id);
    }

    /**
     * BasePolicy-Interface implementieren
     */
    protected function getUserRole(User $user, $model): ?string
    {
        return $this->getUserFolderRole($user, $model);
    }
}
