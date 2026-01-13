<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$folder->name" icon="heroicon-o-folder" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $folder->name }}</h1>
                
                @if($folder->description)
                    <div class="mt-4">
                        <p class="text-[var(--ui-secondary)]">{{ $folder->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Unterordner Section --}}
        @if($subFolders->count() > 0 || auth()->user()->can('update', $folder))
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Unterordner</h2>
                    @can('update', $folder)
                        <x-ui-button variant="primary" size="sm" wire:click="createSubFolder">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Unterordner erstellen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>

                @if($subFolders->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($subFolders as $subFolder)
                            <a href="{{ route('notes.folders.show', $subFolder) }}" class="block">
                                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-shadow p-6 h-full">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $subFolder->name }}</h3>
                                            @if($subFolder->description)
                                                <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $subFolder->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 mt-4">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[var(--ui-primary-5)] text-[var(--ui-primary)] text-xs font-medium">
                                            @svg('heroicon-o-folder', 'w-3 h-3')
                                            Ordner
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    @can('update', $folder)
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                                @svg('heroicon-o-folder', 'w-8 h-8 text-[var(--ui-muted)]')
                            </div>
                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Unterordner</h3>
                            <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deinen ersten Unterordner.</p>
                            <x-ui-button variant="primary" size="sm" wire:click="createSubFolder">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Unterordner erstellen</span>
                                </span>
                            </x-ui-button>
                        </div>
                    @endcan
                @endif
            </div>
        @endif

        {{-- Notizen Section --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Notizen</h2>
                @can('update', $folder)
                    <x-ui-button variant="primary" size="sm" wire:click="createNote">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Notiz erstellen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>

            @if($notes->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($notes as $note)
                        <a href="{{ route('notes.notes.show', $note) }}" class="block">
                            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-shadow p-6 h-full">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $note->name }}</h3>
                                        @if($note->content)
                                            <p class="text-sm text-[var(--ui-muted)] line-clamp-3">{{ strip_tags($note->content) }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 mt-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[var(--ui-primary-5)] text-[var(--ui-primary)] text-xs font-medium">
                                        @svg('heroicon-o-document-text', 'w-3 h-3')
                                        Notiz
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-document-text', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Notizen</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deine erste Notiz in diesem Ordner.</p>
                    @can('update', $folder)
                        <x-ui-button variant="primary" size="sm" wire:click="createNote">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Notiz erstellen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Ordner-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $folder)
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="createSubFolder" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-folder-plus','w-4 h-4')
                                    <span>Unterordner</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="createNote" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-document-plus','w-4 h-4')
                                    <span>Notiz</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Ordner-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $folder->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($folder->parent)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Übergeordneter Ordner</span>
                                <a href="{{ route('notes.folders.show', $folder->parent) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $folder->parent->name }}
                                </a>
                            </div>
                        @endif
                        @if($folder->done)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Status</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded bg-[var(--ui-success-5)] text-[var(--ui-success)]">
                                    Erledigt
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted)] transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] leading-snug">
                                        {{ $activity['title'] ?? 'Aktivität' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                @svg('heroicon-o-clock', 'w-3 h-3')
                                <span>{{ $activity['time'] ?? '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Änderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
