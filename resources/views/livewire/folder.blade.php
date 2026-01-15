<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$folder->name" icon="heroicon-o-folder" />
    </x-slot>

    <x-ui-page-container class="max-w-4xl mx-auto">
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h1 class="text-4xl font-bold text-[var(--ui-secondary)] mb-2" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;">{{ $folder->name }}</h1>
                    @if($folder->description)
                        <p class="text-[var(--ui-muted)] text-lg">{{ $folder->description }}</p>
                    @endif
                </div>
                @can('update', $folder)
                    <div class="flex items-center gap-2">
                        <button 
                            wire:click="createSubFolder"
                            class="px-4 py-2 text-sm rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-2"
                        >
                            @svg('heroicon-o-folder-plus', 'w-4 h-4')
                            <span>Ordner</span>
                        </button>
                        <button 
                            wire:click="createNote"
                            class="px-4 py-2 text-sm rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity flex items-center gap-2"
                        >
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Notiz</span>
                        </button>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Unterordner Section --}}
        @if($subFolders->count() > 0 || auth()->user()->can('update', $folder))
            <div class="mb-12">
                @if($subFolders->count() > 0)
                    <div class="space-y-2">
                        @foreach($subFolders as $subFolder)
                            <a 
                                href="{{ route('notes.folders.show', $subFolder) }}" 
                                wire:navigate 
                                class="block p-4 rounded-lg border border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/40 hover:bg-[var(--ui-muted-5)] transition-all group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center">
                                        @svg('heroicon-o-folder', 'w-6 h-6 text-[var(--ui-primary)] group-hover:scale-110 transition-transform')
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $subFolder->name }}</h3>
                                        @if($subFolder->description)
                                            <p class="text-sm text-[var(--ui-muted)]">{{ $subFolder->description }}</p>
                                        @endif
                                    </div>
                                    <div class="flex-shrink-0 text-sm text-[var(--ui-muted)]">
                                        {{ $subFolder->children()->count() + $subFolder->notes()->count() }} Einträge
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    @can('update', $folder)
                        <div class="text-center py-12 border border-dashed border-[var(--ui-border)] rounded-lg">
                            <p class="text-sm text-[var(--ui-muted)] mb-4">Noch keine Unterordner</p>
                            <button 
                                wire:click="createSubFolder"
                                class="text-sm text-[var(--ui-primary)] hover:underline"
                            >
                                Ersten Unterordner erstellen
                            </button>
                        </div>
                    @endcan
                @endif
            </div>
        @endif

        {{-- Notizen Section --}}
        <div>
            @if($notes->count() > 0)
                <div class="space-y-2">
                    @foreach($notes as $note)
                        <a 
                            href="{{ route('notes.notes.show', $note) }}" 
                            wire:navigate 
                            class="block p-4 rounded-lg border border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/40 hover:bg-[var(--ui-muted-5)] transition-all group"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center mt-1">
                                    @svg('heroicon-o-document-text', 'w-5 h-5 text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)] transition-colors')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $note->name }}</h3>
                                    @if($note->content)
                                        <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ strip_tags(mb_substr($note->content, 0, 150)) }}</p>
                                    @else
                                        <p class="text-sm text-[var(--ui-muted)] italic">Leer</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 text-xs text-[var(--ui-muted)]">
                                    {{ $note->updated_at->format('d.m.Y') }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 border border-dashed border-[var(--ui-border)] rounded-lg">
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Noch keine Notizen</p>
                    @can('update', $folder)
                        <button 
                            wire:click="createNote"
                            class="text-sm text-[var(--ui-primary)] hover:underline"
                        >
                            Erste Notiz erstellen
                        </button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Navigation" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Ordner-Baum --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Ordner-Struktur</h3>
                    <div class="space-y-1 max-h-[400px] overflow-y-auto">
                        @php
                            $folderTree = $this->getFolderTree();
                        @endphp
                        @foreach($folderTree as $item)
                            <a 
                                href="{{ route('notes.folders.show', $item['folder']) }}" 
                                wire:navigate
                                class="flex items-center gap-2 px-3 py-2 rounded-md transition-colors {{ $item['isActive'] ? 'bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/20' : 'hover:bg-[var(--ui-muted-5)]' }}"
                                style="padding-left: {{ ($item['level'] * 1.5) + 0.75 }}rem;"
                            >
                                @if($item['hasChildren'])
                                    @svg('heroicon-o-folder', 'w-4 h-4 {{ $item["isActive"] ? "text-[var(--ui-primary)]" : "text-[var(--ui-muted)]" }}')
                                @else
                                    @svg('heroicon-o-folder', 'w-4 h-4 {{ $item["isActive"] ? "text-[var(--ui-primary)]" : "text-[var(--ui-muted)]" }}')
                                @endif
                                <span class="text-sm {{ $item['isActive'] ? 'font-semibold text-[var(--ui-primary)]' : 'text-[var(--ui-secondary)]' }}">
                                    {{ $item['folder']->name }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Aktionen --}}
                @can('update', $folder)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                        <div class="flex flex-col gap-2">
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
                        </div>
                    </div>
                @endcan

                {{-- Ordner-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $folder->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($folder->parent)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                                <span class="text-sm text-[var(--ui-muted)]">Übergeordnet</span>
                                <a href="{{ route('notes.folders.show', $folder->parent) }}" wire:navigate class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $folder->parent->name }}
                                </a>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-sm text-[var(--ui-muted)]">Unterordner</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $subFolders->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-sm text-[var(--ui-muted)]">Notizen</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $notes->count() }}
                            </span>
                        </div>
                        @if($folder->done)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
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
