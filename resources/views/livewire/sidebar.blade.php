{{-- Notes Sidebar - Struktur nach Brands-Vorbild --}}
<div 
    x-data="{
        init() {
            // Zustand aus localStorage laden beim Initialisieren
            const savedState = localStorage.getItem('notes.showAllFolders');
            if (savedState !== null) {
                @this.set('showAllFolders', savedState === 'true');
            }
            
            // Erweiterte Ordner aus localStorage laden (falls vorhanden, sonst Standard: alle erweitert)
            const expandedState = localStorage.getItem('notes.expandedFolders');
            if (expandedState) {
                try {
                    const expanded = JSON.parse(expandedState);
                    if (Array.isArray(expanded) && expanded.length > 0) {
                        @this.set('expandedFolders', expanded);
                    }
                } catch (e) {
                    console.error('Fehler beim Laden der erweiterten Ordner:', e);
                }
            }
        }
    }"
>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-2 text-xs italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-1">
        Notizen
    </div>
    
    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('notes.dashboard')">
            @svg('heroicon-o-home', 'w-3.5 h-3.5 text-[var(--ui-secondary)]')
            <span class="ml-1.5 text-xs">Dashboard</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Neuer Ordner --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createFolder">
            @svg('heroicon-o-plus-circle', 'w-3.5 h-3.5 text-[var(--ui-secondary)]')
            <span class="ml-1.5 text-xs">Neuer Ordner</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('notes.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createFolder" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Ordner-Baum (Datei-Explorer-ähnlich) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            @if($rootFolders->isNotEmpty())
                <x-ui-sidebar-list :label="'Ordner' . ($showAllFolders ? ' (' . $allFoldersCount . ')' : '')">
                    @foreach($rootFolders as $folder)
                        @include('notes::livewire.partials.folder-tree-item', [
                            'folder' => $folder,
                            'level' => 0
                        ])
                    @endforeach
                </x-ui-sidebar-list>
            @elseif($folders->isNotEmpty())
                {{-- Fallback: Nur Root-Ordner anzeigen --}}
                <x-ui-sidebar-list :label="'Ordner' . ($showAllFolders ? ' (' . $allFoldersCount . ')' : '')">
                    @foreach($folders as $folder)
                        <x-ui-sidebar-item :href="route('notes.folders.show', ['notesFolder' => $folder])">
                            @svg('heroicon-o-folder', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium">{{ $folder->name }}</div>
                            </div>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Button zum Ein-/Ausblenden aller Ordner --}}
            @if($hasMoreFolders)
                <div class="px-3 py-2">
                    <button 
                        type="button" 
                        wire:click="toggleShowAllFolders"
                        x-on:click="localStorage.setItem('notes.showAllFolders', (!$wire.showAllFolders).toString())"
                        class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                    >
                        @if($showAllFolders)
                            @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            <span>Nur meine Ordner</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Alle Ordner anzeigen</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- Keine Ordner --}}
            @if($folders->isEmpty() && $folderTree->isEmpty())
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    @if($showAllFolders)
                        Keine Ordner
                    @else
                        Keine Ordner vorhanden
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
