<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Notizen" icon="heroicon-o-document-text" />
    </x-slot>

    <x-ui-page-container>
        {{-- Search & Action Bar --}}
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Search Input --}}
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        @svg('heroicon-o-magnifying-glass', 'w-5 h-5 text-[var(--ui-muted)]')
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Notizen durchsuchen..."
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-[var(--ui-border)] bg-[var(--ui-background)] text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/30 focus:border-[var(--ui-primary)] transition-all text-sm"
                    />
                    @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                        </button>
                    @endif
                </div>

                {{-- Quick Actions --}}
                <div class="flex items-center gap-2">
                    <button
                        wire:click="createQuickNote"
                        class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity text-sm font-medium whitespace-nowrap"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="hidden sm:inline">Neue Notiz</span>
                    </button>
                    <button
                        wire:click="createFolder"
                        class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-sm font-medium text-[var(--ui-secondary)] whitespace-nowrap"
                    >
                        @svg('heroicon-o-folder-plus', 'w-4 h-4')
                        <span class="hidden sm:inline">Ordner</span>
                    </button>
                    <button
                        wire:click="toggleViewMode"
                        class="inline-flex items-center justify-center w-11 h-11 rounded-xl border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-secondary)]"
                        title="{{ $viewMode === 'grid' ? 'Listenansicht' : 'Galerieansicht' }}"
                    >
                        @if($viewMode === 'grid')
                            @svg('heroicon-o-list-bullet', 'w-4 h-4')
                        @else
                            @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                        @endif
                    </button>
                </div>
            </div>

            {{-- Tags Bar --}}
            @if($uniqueTags->count() > 0)
                <div class="flex items-center gap-2 mt-3 overflow-x-auto pb-1 scrollbar-hide">
                    <button
                        wire:click="$toggle('showPinnedOnly')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-all {{ $showPinnedOnly ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)]' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] border border-[var(--ui-border)]/50' }}"
                    >
                        @svg('heroicon-s-star', 'w-3 h-3')
                        Angepinnt
                    </button>
                    @foreach($uniqueTags as $tag => $count)
                        <button
                            wire:click="setFilterTag('{{ $tag }}')"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-all {{ $filterTag === $tag ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)]' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] border border-[var(--ui-border)]/50' }}"
                        >
                            #{{ $tag }}
                            <span class="opacity-60">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pinned Section --}}
        @if($pinnedNotes->count() > 0 || $pinnedFolders->count() > 0)
            <div class="mb-8">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3 flex items-center gap-1.5">
                    @svg('heroicon-s-star', 'w-3.5 h-3.5 text-amber-400')
                    Angepinnt
                </h2>
                <div class="{{ $viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3' : 'space-y-2' }}">
                    @foreach($pinnedFolders as $folder)
                        @include('notes::livewire.partials.folder-card', ['folder' => $folder, 'viewMode' => $viewMode])
                    @endforeach
                    @foreach($pinnedNotes as $note)
                        @include('notes::livewire.partials.note-card', ['note' => $note, 'viewMode' => $viewMode])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Folders Section --}}
        @if($folders->where('is_pinned', false)->count() > 0)
            <div class="mb-8">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">
                    Ordner
                </h2>
                <div class="{{ $viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3' : 'space-y-2' }}">
                    @foreach($folders->where('is_pinned', false) as $folder)
                        @include('notes::livewire.partials.folder-card', ['folder' => $folder, 'viewMode' => $viewMode])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Notes Section --}}
        @if($recentNotes->count() > 0)
            <div class="mb-8">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">
                    {{ $search ? 'Suchergebnisse' : 'Zuletzt bearbeitet' }}
                </h2>
                <div class="{{ $viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3' : 'space-y-2' }}">
                    @foreach($recentNotes as $note)
                        @include('notes::livewire.partials.note-card', ['note' => $note, 'viewMode' => $viewMode])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        @if($allNotes->count() === 0 && $folders->count() === 0)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-[var(--ui-primary-5)] flex items-center justify-center mb-4">
                    @svg('heroicon-o-document-text', 'w-8 h-8 text-[var(--ui-primary)]')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Notizen</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-6 max-w-sm">Erstelle deine erste Notiz oder einen Ordner, um loszulegen.</p>
                <div class="flex items-center gap-3">
                    <button
                        wire:click="createQuickNote"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity text-sm font-medium"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neue Notiz
                    </button>
                    <button
                        wire:click="createFolder"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-sm font-medium text-[var(--ui-secondary)]"
                    >
                        @svg('heroicon-o-folder-plus', 'w-4 h-4')
                        Neuer Ordner
                    </button>
                </div>
            </div>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Stats --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Statistiken</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <div class="text-xl font-bold text-[var(--ui-secondary)]">{{ $activeNotes }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)]">Notizen</div>
                        </div>
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <div class="text-xl font-bold text-[var(--ui-secondary)]">{{ $activeFolders }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)]">Ordner</div>
                        </div>
                    </div>
                </div>

                {{-- Keyboard Shortcuts Help --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Tastenkürzel</h3>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Suchen</span>
                            <kbd class="px-1.5 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">/</kbd>
                        </div>
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Neue Notiz</span>
                            <kbd class="px-1.5 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">N</kbd>
                        </div>
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Speichern</span>
                            <kbd class="px-1.5 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">⌘S</kbd>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    @push('styles')
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener('keydown', function(e) {
        // Focus search on "/" key
        if (e.key === '/' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
            if (searchInput) searchInput.focus();
        }
        // New note on "n" key
        if (e.key === 'n' && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            window.Livewire?.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'))?.call('createQuickNote');
        }
    });
    </script>
    @endpush
</x-ui-page>
