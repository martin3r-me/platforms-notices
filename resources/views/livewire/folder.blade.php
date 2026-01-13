<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$folder->name" icon="heroicon-o-folder">
            {{-- Breadcrumbs --}}
            <div class="flex items-center gap-2 text-sm text-[var(--ui-muted)] mt-1">
                @foreach($this->getBreadcrumbs() as $index => $crumb)
                    @if($index > 0)
                        @svg('heroicon-o-chevron-right', 'w-3 h-3')
                    @endif
                    @if($index < count($this->getBreadcrumbs()) - 1)
                        <a href="{{ $crumb['url'] }}" wire:navigate class="hover:text-[var(--ui-secondary)] transition-colors">
                            {{ $crumb['name'] }}
                        </a>
                    @else
                        <span class="text-[var(--ui-secondary)] font-medium">{{ $crumb['name'] }}</span>
                    @endif
                @endforeach
            </div>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $folder->name }}</h1>
                        @if($folder->description)
                            <p class="text-[var(--ui-secondary)] text-lg">{{ $folder->description }}</p>
                        @endif
                    </div>
                    @can('update', $folder)
                        <div class="flex items-center gap-2">
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="createSubFolder">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-folder-plus', 'w-4 h-4')
                                    <span>Unterordner</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="primary" size="sm" wire:click="createNote">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Notiz</span>
                                </span>
                            </x-ui-button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Unterordner Section --}}
        @if($subFolders->count() > 0 || auth()->user()->can('update', $folder))
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-folder', 'w-5 h-5')
                        <span>Unterordner</span>
                        <span class="text-sm font-normal text-[var(--ui-muted)]">({{ $subFolders->count() }})</span>
                    </h2>
                </div>

                @if($subFolders->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($subFolders as $subFolder)
                            <a href="{{ route('notes.folders.show', $subFolder) }}" wire:navigate class="block group">
                                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-[var(--ui-primary)]/40 transition-all p-6 h-full">
                                    <div class="flex items-start gap-3 mb-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-[var(--ui-primary-5)] rounded-lg flex items-center justify-center group-hover:bg-[var(--ui-primary-10)] transition-colors">
                                            @svg('heroicon-o-folder', 'w-6 h-6 text-[var(--ui-primary)]')
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1 truncate">{{ $subFolder->name }}</h3>
                                            @if($subFolder->description)
                                                <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $subFolder->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                        <span>{{ $subFolder->children()->count() }} Ordner</span>
                                        <span>•</span>
                                        <span>{{ $subFolder->notes()->count() }} Notizen</span>
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
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                    @svg('heroicon-o-document-text', 'w-5 h-5')
                    <span>Notizen</span>
                    <span class="text-sm font-normal text-[var(--ui-muted)]">({{ $notes->count() }})</span>
                </h2>
            </div>

            @if($notes->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($notes as $note)
                        <a href="{{ route('notes.notes.show', $note) }}" wire:navigate class="block group">
                            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-[var(--ui-primary)]/40 transition-all p-6 h-full">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-[var(--ui-primary-5)] rounded-lg flex items-center justify-center group-hover:bg-[var(--ui-primary-10)] transition-colors">
                                        @svg('heroicon-o-document-text', 'w-6 h-6 text-[var(--ui-primary)]')
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1 truncate">{{ $note->name }}</h3>
                                        @if($note->content)
                                            <p class="text-sm text-[var(--ui-muted)] line-clamp-3">{{ strip_tags($note->content) }}</p>
                                        @else
                                            <p class="text-sm text-[var(--ui-muted)] italic">Leer</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                    <span>{{ $note->updated_at->format('d.m.Y') }}</span>
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
