<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-document-text" />
    </x-slot>

    <x-ui-page-container>

            {{-- Main Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-ui-dashboard-tile
                    title="Aktive Ordner"
                    :count="$activeFolders"
                    subtitle="von {{ $totalFolders }}"
                    icon="folder"
                    variant="secondary"
                    size="lg"
                />
                <x-ui-dashboard-tile
                    title="Aktive Notizen"
                    :count="$activeNotes"
                    subtitle="von {{ $totalNotes }}"
                    icon="document-text"
                    variant="secondary"
                    size="lg"
                />
            </div>

            <x-ui-panel title="Meine aktiven Ordner" subtitle="Top 5 Ordner">
                <div class="grid grid-cols-1 gap-3">
                    @forelse($activeFoldersList as $folder)
                        @php
                            $href = route('notes.folders.show', ['notesFolder' => $folder['id'] ?? null]);
                        @endphp
                        <a href="{{ $href }}" class="flex items-center gap-3 p-3 rounded-md border border-[var(--ui-border)] bg-white hover:bg-[var(--ui-muted-5)] transition">
                            <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center">
                                @svg('heroicon-o-folder', 'w-5 h-5')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $folder['name'] ?? 'Ordner' }}</div>
                                <div class="text-xs text-[var(--ui-muted)] truncate">
                                    {{ $folder['subtitle'] ?? '' }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3 text-sm text-[var(--ui-muted)] bg-white rounded-md border border-[var(--ui-border)]">Keine Ordner gefunden.</div>
                    @endforelse
                </div>
            </x-ui-panel>

            <x-ui-panel title="Meine aktiven Notizen" subtitle="Top 5 Notizen">
                <div class="grid grid-cols-1 gap-3">
                    @forelse($activeNotesList as $note)
                        @php
                            $href = route('notes.notes.show', ['notesNote' => $note['id'] ?? null]);
                        @endphp
                        <a href="{{ $href }}" class="flex items-center gap-3 p-3 rounded-md border border-[var(--ui-border)] bg-white hover:bg-[var(--ui-muted-5)] transition">
                            <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center">
                                @svg('heroicon-o-document-text', 'w-5 h-5')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $note['name'] ?? 'Notiz' }}</div>
                                <div class="text-xs text-[var(--ui-muted)] truncate">
                                    {{ $note['subtitle'] ?? '' }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3 text-sm text-[var(--ui-muted)] bg-white rounded-md border border-[var(--ui-border)]">Keine Notizen gefunden.</div>
                    @endforelse
                </div>
            </x-ui-panel>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary-outline" size="sm" wire:click="createFolder" class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Neuer Ordner</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schnellstatistiken</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Aktive Ordner</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $activeFolders ?? 0 }} Ordner</div>
                        </div>
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Aktive Notizen</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $activeNotes ?? 0 }} Notizen</div>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity (Dummy) --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Letzte Aktivitäten</h3>
                    <div class="space-y-2 text-sm">
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard geladen</div>
                            <div class="text-[var(--ui-muted)] text-xs">vor 1 Minute</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard geladen</div>
                        <div class="text-[var(--ui-muted)]">vor 1 Minute</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
