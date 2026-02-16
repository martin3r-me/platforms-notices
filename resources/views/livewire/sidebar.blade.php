{{-- Notes Sidebar --}}
<div
    x-data="{
        init() {
            const savedState = localStorage.getItem('notes.showAllFolders');
            if (savedState !== null) {
                @this.set('showAllFolders', savedState === 'true');
            }
            const expandedState = localStorage.getItem('notes.expandedFolders');
            if (expandedState) {
                try {
                    const expanded = JSON.parse(expandedState);
                    if (Array.isArray(expanded) && expanded.length > 0) {
                        @this.set('expandedFolders', expanded);
                    }
                } catch (e) {}
            }
        }
    }"
>
    {{-- Module Header --}}
    <div x-show="!collapsed" class="p-2 text-xs italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-1">
        Notizen
    </div>

    {{-- Quick Search --}}
    <div x-show="!collapsed" class="px-2 py-1.5">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                @svg('heroicon-o-magnifying-glass', 'w-3 h-3 text-[var(--ui-muted)]')
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="sidebarSearch"
                placeholder="Suchen..."
                class="w-full pl-7 pr-2 py-1 text-xs rounded-md border border-[var(--ui-border)]/50 bg-[var(--ui-muted-5)] text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]/30 focus:border-[var(--ui-primary)]"
            />
        </div>
    </div>

    {{-- Navigation --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('notes.dashboard')">
            @svg('heroicon-o-home', 'w-3.5 h-3.5 text-[var(--ui-secondary)]')
            <span class="ml-1.5 text-xs">Dashboard</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Quick Actions --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createQuickNote">
            @svg('heroicon-o-document-plus', 'w-3.5 h-3.5 text-[var(--ui-secondary)]')
            <span class="ml-1.5 text-xs">Neue Notiz</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item wire:click="createFolder">
            @svg('heroicon-o-folder-plus', 'w-3.5 h-3.5 text-[var(--ui-secondary)]')
            <span class="ml-1.5 text-xs">Neuer Ordner</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed Icons --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('notes.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createQuickNote" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] w-full">
            @svg('heroicon-o-document-plus', 'w-5 h-5')
        </button>
        <button type="button" wire:click="createFolder" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] w-full mt-1">
            @svg('heroicon-o-folder-plus', 'w-5 h-5')
        </button>
    </div>

    {{-- Pinned Notes --}}
    @if($pinnedNotes->count() > 0)
        <div x-show="!collapsed" class="px-1 py-1 border-b border-[var(--ui-border)]">
            <div class="px-1 pb-1 flex items-center gap-1">
                @svg('heroicon-s-star', 'w-3 h-3 text-amber-400')
                <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">Angepinnt</span>
            </div>
            <div class="flex flex-col gap-0.5">
                @foreach($pinnedNotes as $pinnedNote)
                    <a
                        href="{{ route('notes.notes.show', $pinnedNote) }}"
                        wire:navigate
                        class="flex items-center gap-1.5 py-1 px-1.5 rounded-md hover:bg-[var(--ui-muted-5)] transition-colors"
                    >
                        @svg('heroicon-o-document-text', 'w-3 h-3 flex-shrink-0 text-[var(--ui-primary)]')
                        <span class="text-xs truncate">{{ $pinnedNote->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Folder Tree --}}
    <div>
        <div class="mt-1" x-show="!collapsed">
            @if($rootFolders->isNotEmpty())
                <div x-show="!collapsed" class="px-1 py-1 border-b border-[var(--ui-border)]">
                    <div class="px-1 pb-1 text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">{{ 'Ordner' . ($showAllFolders ? ' (' . $allFoldersCount . ')' : '') }}</div>
                    <div class="flex flex-col gap-0.5">
                        @foreach($rootFolders as $folder)
                            @include('notes::livewire.partials.folder-tree-item', [
                                'folder' => $folder,
                                'level' => 0
                            ])
                        @endforeach
                    </div>
                </div>
            @elseif($folders->isNotEmpty())
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

    {{-- Recent Notes --}}
    @if($recentNotes->count() > 0)
        <div x-show="!collapsed" class="px-1 py-1 border-b border-[var(--ui-border)]">
            <div class="px-1 pb-1 text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">Zuletzt bearbeitet</div>
            <div class="flex flex-col gap-0.5">
                @foreach($recentNotes as $recentNote)
                    <a
                        href="{{ route('notes.notes.show', $recentNote) }}"
                        wire:navigate
                        class="flex items-center gap-1.5 py-1 px-1.5 rounded-md hover:bg-[var(--ui-muted-5)] transition-colors"
                    >
                        @svg('heroicon-o-document-text', 'w-3 h-3 flex-shrink-0 text-[var(--ui-muted)]')
                        <span class="text-xs truncate text-[var(--ui-muted)]">{{ $recentNote->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
