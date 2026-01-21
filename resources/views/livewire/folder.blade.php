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
            <div class="p-4 space-y-4">
                {{-- Ordner umbenennen --}}
                @can('update', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Ordner</h3>
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="text"
                                    value="{{ $folder->name }}"
                                    wire:model.blur="folder.name"
                                    wire:change="updateFolderName($event.target.value)"
                                    class="flex-1 px-2 py-1 text-xs rounded-md border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                                    placeholder="Ordner-Name"
                                />
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Aktionen --}}
                @can('update', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Aktionen</h3>
                        <div class="flex flex-col gap-1.5">
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="createSubFolder" class="w-full text-xs py-1.5">
                                <span class="inline-flex items-center gap-1.5">
                                    @svg('heroicon-o-folder-plus','w-3.5 h-3.5')
                                    <span>Unterordner</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="createNote" class="w-full text-xs py-1.5">
                                <span class="inline-flex items-center gap-1.5">
                                    @svg('heroicon-o-document-plus','w-3.5 h-3.5')
                                    <span>Notiz</span>
                                </span>
                            </x-ui-button>
                        </div>
                    </div>
                @endcan

                {{-- Benutzer-Verwaltung --}}
                @can('invite', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Benutzer</h3>
                        <div class="space-y-2">
                            {{-- User hinzufügen --}}
                            <div class="flex gap-1.5">
                                <select
                                    wire:model="selectedUserId"
                                    class="flex-1 px-2 py-1 text-xs rounded-md border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                                >
                                    <option value="">User auswählen...</option>
                                    @foreach($teamUsers as $teamUser)
                                        @php
                                            $isOwner = $folder->user_id === $teamUser->id;
                                            $isAlreadyAdded = $folderUsers->contains('user_id', $teamUser->id);
                                        @endphp
                                        @if(!$isOwner && !$isAlreadyAdded)
                                            <option value="{{ $teamUser->id }}">{{ $teamUser->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <select
                                    wire:model="selectedRole"
                                    class="px-2 py-1 text-xs rounded-md border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                                >
                                    <option value="viewer">Viewer</option>
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button
                                    wire:click="addFolderUser($selectedUserId, $selectedRole)"
                                    wire:loading.attr="disabled"
                                    class="px-2 py-1 text-xs rounded-md bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity disabled:opacity-50"
                                    @if(!$selectedUserId) disabled @endif
                                >
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                </button>
                            </div>

                            {{-- Bereits hinzugefügte User --}}
                            <div class="space-y-1">
                                @foreach($folderUsers as $folderUser)
                                    <div class="flex items-center justify-between px-2 py-1.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs font-medium text-[var(--ui-secondary)] truncate">
                                                {{ $folderUser->user->name ?? 'Unbekannt' }}
                                            </div>
                                            @if($folderUser->user_id === $folder->user_id)
                                                <div class="text-[10px] text-[var(--ui-muted)]">Owner</div>
                                            @else
                                                <select
                                                    wire:change="changeFolderUserRole({{ $folderUser->user_id }}, $event.target.value)"
                                                    class="text-[10px] mt-0.5 px-1.5 py-0.5 rounded border border-[var(--ui-border)] bg-[var(--ui-background)] text-[var(--ui-secondary)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                                                    @if($folderUser->role === 'owner' || !auth()->user()->can('changeRole', $folder)) disabled @endif
                                                >
                                                    <option value="owner" @if($folderUser->role === 'owner') selected @endif>Owner</option>
                                                    <option value="admin" @if($folderUser->role === 'admin') selected @endif>Admin</option>
                                                    <option value="member" @if($folderUser->role === 'member') selected @endif>Member</option>
                                                    <option value="viewer" @if($folderUser->role === 'viewer') selected @endif>Viewer</option>
                                                </select>
                                            @endif
                                        </div>
                                        @if($folderUser->role !== 'owner' && $folder->user_id !== $folderUser->user_id && auth()->user()->can('removeMember', $folder))
                                            <button
                                                wire:click="removeFolderUser({{ $folderUser->user_id }})"
                                                wire:confirm="Möchten Sie diesen Benutzer wirklich entfernen?"
                                                class="ml-2 p-1 text-red-500 hover:text-red-700 transition-colors"
                                                title="Entfernen"
                                            >
                                                @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Löschen --}}
                @can('delete', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Gefährlich</h3>
                        <button
                            wire:click="deleteFolder"
                            wire:confirm="Möchten Sie diesen Ordner wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden."
                            class="w-full px-3 py-1.5 text-xs rounded-md border border-red-500/30 bg-red-500/10 text-red-600 hover:bg-red-500/20 transition-colors flex items-center justify-center gap-1.5"
                        >
                            @svg('heroicon-o-trash','w-3.5 h-3.5')
                            <span>Ordner löschen</span>
                        </button>
                    </div>
                @endcan

                {{-- Ordner-Details --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Details</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-xs text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">
                                {{ $folder->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($folder->parent)
                            <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                                <span class="text-xs text-[var(--ui-muted)]">Übergeordnet</span>
                                <a href="{{ route('notes.folders.show', $folder->parent) }}" wire:navigate class="text-xs text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $folder->parent->name }}
                                </a>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-xs text-[var(--ui-muted)]">Unterordner</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">
                                {{ $subFolders->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-xs text-[var(--ui-muted)]">Notizen</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">
                                {{ $notes->count() }}
                            </span>
                        </div>
                        @if($folder->done)
                            <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                                <span class="text-xs text-[var(--ui-muted)]">Status</span>
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-[var(--ui-success-5)] text-[var(--ui-success)]">
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
