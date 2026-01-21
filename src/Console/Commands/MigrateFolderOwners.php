<?php

namespace Platform\Notes\Console\Commands;

use Illuminate\Console\Command;
use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesFolderUser;
use Platform\Notes\Enums\FolderRole;

class MigrateFolderOwners extends Command
{
    protected $signature = 'notes:migrate-folder-owners
        {--dry-run : Zeigt nur, was migriert würde, ohne Änderungen vorzunehmen}
        {--force : Überschreibt bestehende Einträge}';

    protected $description = 'Migriert vorhandene Ordner-Owner von user_id in die notes_folder_users Pivot-Tabelle';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY-RUN Modus: Es werden keine Änderungen vorgenommen.');
        }

        $this->info('📁 Starte Migration der Ordner-Owner...');

        // Alle Ordner mit user_id laden
        $folders = NotesFolder::whereNotNull('user_id')->get();

        if ($folders->isEmpty()) {
            $this->warn('Keine Ordner mit user_id gefunden.');
            return Command::SUCCESS;
        }

        $this->info("Gefunden: {$folders->count()} Ordner mit user_id");

        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        $invalidUsers = 0;

        $progressBar = $this->output->createProgressBar($folders->count());
        $progressBar->start();

        foreach ($folders as $folder) {
            try {
                // Prüfen, ob der User noch existiert
                $user = \Platform\Core\Models\User::find($folder->user_id);
                if (!$user) {
                    $invalidUsers++;
                    if ($this->getOutput()->isVerbose()) {
                        $this->line("\n⚠ Ordner #{$folder->id} ({$folder->name}): User #{$folder->user_id} existiert nicht mehr");
                    }
                    $progressBar->advance();
                    continue;
                }

                // Prüfen, ob bereits ein Eintrag existiert
                $existing = NotesFolderUser::where('folder_id', $folder->id)
                    ->where('user_id', $folder->user_id)
                    ->first();

                if ($existing) {
                    if ($force) {
                        // Bestehenden Eintrag aktualisieren
                        if (!$dryRun) {
                            $existing->update([
                                'role' => FolderRole::OWNER->value,
                            ]);
                        }
                        $migrated++;
                        if ($this->getOutput()->isVerbose()) {
                            $this->line("\n✓ Ordner #{$folder->id} ({$folder->name}): Owner-Eintrag aktualisiert");
                        }
                    } else {
                        $skipped++;
                        if ($this->getOutput()->isVerbose()) {
                            $this->line("\n⊘ Ordner #{$folder->id} ({$folder->name}): Bereits vorhanden (übersprungen)");
                        }
                    }
                } else {
                    // Neuen Eintrag erstellen
                    if (!$dryRun) {
                        NotesFolderUser::create([
                            'folder_id' => $folder->id,
                            'user_id' => $folder->user_id,
                            'role' => FolderRole::OWNER->value,
                        ]);
                    }
                    $migrated++;
                    if ($this->getOutput()->isVerbose()) {
                        $this->line("\n✓ Ordner #{$folder->id} ({$folder->name}): Owner-Eintrag erstellt");
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("\n✗ Fehler bei Ordner #{$folder->id} ({$folder->name}): {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Zusammenfassung
        $this->info('📊 Zusammenfassung:');
        $summary = [
            ['Migriert/Erstellt', $migrated],
            ['Übersprungen', $skipped],
        ];
        
        if ($invalidUsers > 0) {
            $summary[] = ['Ungültige User', $invalidUsers];
        }
        
        if ($errors > 0) {
            $summary[] = ['Fehler', $errors];
        }
        
        $this->table(
            ['Status', 'Anzahl'],
            $summary
        );

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN: Keine Änderungen wurden vorgenommen.');
            $this->info('Führe den Command ohne --dry-run aus, um die Migration durchzuführen.');
        } else {
            $this->info('✅ Migration abgeschlossen!');
        }

        return Command::SUCCESS;
    }
}
