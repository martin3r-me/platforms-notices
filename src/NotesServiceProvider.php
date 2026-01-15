<?php

namespace Platform\Notes;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;

use Platform\Notes\Models\NotesFolder;
use Platform\Notes\Models\NotesNote;
use Platform\Notes\Policies\FolderPolicy;
use Platform\Notes\Policies\NotePolicy;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class NotesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Commands können später hinzugefügt werden
    }

    public function boot(): void
    {
        // Config veröffentlichen & zusammenführen (MUSS VOR registerModule sein!)
        $this->publishes([
            __DIR__.'/../config/notes.php' => config_path('notes.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/notes.php', 'notes');

        // Modul-Registrierung nur, wenn Config & Tabelle vorhanden
        if (
            config()->has('notes.routing') &&
            config()->has('notes.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'notes',
                'title'      => 'Notizen',
                'routing'    => config('notes.routing'),
                'guard'      => config('notes.guard'),
                'navigation' => config('notes.navigation'),
                'sidebar'    => config('notes.sidebar'),
                'billables'  => config('notes.billables', []),
            ]);
        }

        // Routen nur laden, wenn das Modul registriert wurde
        if (PlatformCore::getModule('notes')) {
            ModuleRouter::group('notes', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Migrations, Views, Livewire-Komponenten
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'notes');
        $this->registerLivewireComponents();

        // Policies registrieren
        $this->registerPolicies();

        // Tools registrieren (für LLM/AI)
        $this->registerTools();
    }
    
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Notes\\Livewire';
        $prefix = 'notes';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    /**
     * Registriert Policies für das Notes-Modul
     */
    protected function registerPolicies(): void
    {
        $policies = [
            NotesFolder::class => FolderPolicy::class,
            NotesNote::class => NotePolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Registriert Tools für das Notes-Modul
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            $registry->register(new \Platform\Notes\Tools\CreateFolderTool());
            $registry->register(new \Platform\Notes\Tools\CreateNoteTool());
            $registry->register(new \Platform\Notes\Tools\DeleteFolderTool());
            $registry->register(new \Platform\Notes\Tools\DeleteNoteTool());
            $registry->register(new \Platform\Notes\Tools\UpdateNoteTool());
            $registry->register(new \Platform\Notes\Tools\GetNoteTool());
        } catch (\Throwable $e) {
            // Silent fail - Tool-Registry könnte nicht verfügbar sein
        }
    }
}
