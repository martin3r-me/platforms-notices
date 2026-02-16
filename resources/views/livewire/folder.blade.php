<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$folder->name" icon="heroicon-o-folder" />
    </x-slot>

    <x-ui-page-container class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl md:text-3xl font-bold text-[var(--ui-secondary)] truncate" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;">
                            {{ $folder->name }}
                        </h1>
                        <button
                            wire:click="toggleFolderPin"
                            class="flex-shrink-0 p-1 rounded-lg hover:bg-[var(--ui-muted-5)] transition-colors {{ $folder->is_pinned ? 'text-amber-400' : 'text-[var(--ui-muted)]' }}"
                        >
                            @if($folder->is_pinned)
                                @svg('heroicon-s-star', 'w-5 h-5')
                            @else
                                @svg('heroicon-o-star', 'w-5 h-5')
                            @endif
                        </button>
                    </div>
                    @if($folder->description)
                        <p class="text-sm text-[var(--ui-muted)]">{{ $folder->description }}</p>
                    @endif

                    {{-- Tags --}}
                    <div class="flex items-center gap-2 mt-2" x-data="{ showTagInput: false, newTag: '' }">
                        @foreach($folder->tags ?? [] as $tag)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded-full text-xs font-medium">
                                #{{ $tag }}
                                @can('update', $folder)
                                    <button wire:click="removeFolderTag('{{ $tag }}')" class="hover:text-red-500 transition-colors">
                                        @svg('heroicon-o-x-mark', 'w-3 h-3')
                                    </button>
                                @endcan
                            </span>
                        @endforeach
                        @can('update', $folder)
                            <template x-if="showTagInput">
                                <input
                                    type="text"
                                    x-model="newTag"
                                    @keydown.enter.prevent="if(newTag.trim()) { $wire.addFolderTag(newTag.trim()); newTag = ''; showTagInput = false; }"
                                    @keydown.escape="showTagInput = false; newTag = ''"
                                    @blur="if(newTag.trim()) { $wire.addFolderTag(newTag.trim()); } newTag = ''; showTagInput = false;"
                                    placeholder="Tag..."
                                    class="px-2 py-0.5 text-xs rounded-full border border-[var(--ui-primary)]/30 bg-transparent focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]/30 w-20"
                                    x-init="$el.focus()"
                                />
                            </template>
                            <button
                                @click="showTagInput = true"
                                x-show="!showTagInput"
                                class="inline-flex items-center gap-1 px-2 py-0.5 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] rounded-full transition-colors"
                            >
                                @svg('heroicon-o-plus', 'w-3 h-3')
                                Tag
                            </button>
                        @endcan
                    </div>
                </div>

                @can('update', $folder)
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            wire:click="createSubFolder"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-secondary)]"
                        >
                            @svg('heroicon-o-folder-plus', 'w-4 h-4')
                            <span class="hidden sm:inline">Ordner</span>
                        </button>
                        <button
                            wire:click="createNote"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity"
                        >
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span class="hidden sm:inline">Notiz</span>
                        </button>
                    </div>
                @endcan
            </div>

            {{-- Search & View Toggle --}}
            <div class="flex items-center gap-2 mt-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        @svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-[var(--ui-muted)]')
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="In diesem Ordner suchen..."
                        class="w-full pl-9 pr-4 py-2 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-background)] text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/30 focus:border-[var(--ui-primary)] transition-all text-sm"
                    />
                </div>
                <button
                    wire:click="toggleViewMode"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-secondary)]"
                >
                    @if($viewMode === 'grid')
                        @svg('heroicon-o-list-bullet', 'w-4 h-4')
                    @else
                        @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                    @endif
                </button>
            </div>
        </div>

        {{-- Subfolders --}}
        @if($subFolders->count() > 0)
            <div class="mb-8">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">
                    Unterordner
                </h2>
                <div class="{{ $viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3' : 'space-y-2' }}">
                    @foreach($subFolders as $subFolder)
                        @include('notes::livewire.partials.folder-card', ['folder' => $subFolder, 'viewMode' => $viewMode])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($notes->count() > 0)
            <div class="mb-8">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">
                    Notizen
                </h2>
                <div class="{{ $viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3' : 'space-y-2' }}">
                    @foreach($notes as $note)
                        @include('notes::livewire.partials.note-card', ['note' => $note, 'viewMode' => $viewMode])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        @if($subFolders->count() === 0 && $notes->count() === 0)
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-[var(--ui-muted-5)] flex items-center justify-center mb-4">
                    @svg('heroicon-o-folder-open', 'w-7 h-7 text-[var(--ui-muted)]')
                </div>
                <h3 class="text-base font-semibold text-[var(--ui-secondary)] mb-1">Ordner ist leer</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-5">Erstelle Notizen oder Unterordner.</p>
                @can('update', $folder)
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="createNote"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity text-sm font-medium"
                        >
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Notiz erstellen
                        </button>
                        <button
                            wire:click="createSubFolder"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-sm text-[var(--ui-secondary)]"
                        >
                            @svg('heroicon-o-folder-plus', 'w-4 h-4')
                            Unterordner
                        </button>
                    </div>
                @endcan
            </div>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Ordner" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Rename --}}
                @can('update', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Name</h3>
                        <input
                            type="text"
                            value="{{ $folder->name }}"
                            wire:model.blur="folder.name"
                            wire:change="updateFolderName($event.target.value)"
                            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                            placeholder="Ordner-Name"
                        />
                    </div>
                @endcan

                {{-- Actions --}}
                @can('update', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Aktionen</h3>
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

                {{-- Members --}}
                @can('invite', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Mitglieder</h3>
                        <div class="space-y-2">
                            <div class="flex gap-1.5">
                                <select
                                    wire:model.live="selectedUserId"
                                    class="flex-1 px-2 py-1 text-xs rounded-lg border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
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
                                    wire:model.live="selectedRole"
                                    class="px-2 py-1 text-xs rounded-lg border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                                >
                                    <option value="viewer">Viewer</option>
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button
                                    wire:click="addFolderUser"
                                    wire:loading.attr="disabled"
                                    x-data="{ selectedUserId: @entangle('selectedUserId') }"
                                    x-bind:disabled="!selectedUserId || selectedUserId === '' || selectedUserId === null"
                                    class="px-2 py-1 text-xs rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                </button>
                            </div>

                            <div class="space-y-1">
                                @foreach($folderUsers as $folderUser)
                                    <div class="flex items-center justify-between px-2 py-1.5 bg-[var(--ui-muted-5)] rounded-lg">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-5 h-5 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center flex-shrink-0">
                                                <span class="text-[9px] font-bold text-[var(--ui-primary)]">{{ mb_substr($folderUser->user->name ?? '?', 0, 1) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-medium text-[var(--ui-secondary)] truncate">
                                                    {{ $folderUser->user->name ?? 'Unbekannt' }}
                                                </div>
                                                @if($folderUser->user_id === $folder->user_id)
                                                    <div class="text-[10px] text-[var(--ui-muted)]">Owner</div>
                                                @else
                                                    <select
                                                        wire:change="changeFolderUserRole({{ $folderUser->user_id }}, $event.target.value)"
                                                        class="text-[10px] mt-0.5 px-1 py-0.5 rounded border border-[var(--ui-border)] bg-[var(--ui-background)] text-[var(--ui-secondary)] focus:outline-none"
                                                        @if($folderUser->role === 'owner' || !auth()->user()->can('changeRole', $folder)) disabled @endif
                                                    >
                                                        <option value="owner" @if($folderUser->role === 'owner') selected @endif>Owner</option>
                                                        <option value="admin" @if($folderUser->role === 'admin') selected @endif>Admin</option>
                                                        <option value="member" @if($folderUser->role === 'member') selected @endif>Member</option>
                                                        <option value="viewer" @if($folderUser->role === 'viewer') selected @endif>Viewer</option>
                                                    </select>
                                                @endif
                                            </div>
                                        </div>
                                        @if($folderUser->role !== 'owner' && $folder->user_id !== $folderUser->user_id && auth()->user()->can('removeMember', $folder))
                                            <button
                                                wire:click="removeFolderUser({{ $folderUser->user_id }})"
                                                wire:confirm="Möchten Sie diesen Benutzer wirklich entfernen?"
                                                class="p-0.5 text-red-400 hover:text-red-600 transition-colors"
                                            >
                                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Details --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Details</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">{{ $folder->created_at->format('d.m.Y') }}</span>
                        </div>
                        @if($folder->parent)
                            <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">Übergeordnet</span>
                                <a href="{{ route('notes.folders.show', $folder->parent) }}" wire:navigate class="text-xs text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $folder->parent->name }}
                                </a>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Unterordner</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">{{ $subFolders->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Notizen</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">{{ $notes->count() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Delete --}}
                @can('delete', $folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Gefährlich</h3>
                        <button
                            wire:click="deleteFolder"
                            wire:confirm="Möchten Sie diesen Ordner wirklich löschen?"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-red-500/30 bg-red-500/5 text-red-500 hover:bg-red-500/10 transition-colors flex items-center justify-center gap-1.5"
                        >
                            @svg('heroicon-o-trash','w-3.5 h-3.5')
                            <span>Ordner löschen</span>
                        </button>
                    </div>
                @endcan
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-72" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <div class="py-8 text-center">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-[var(--ui-muted-5)] mb-3">
                        @svg('heroicon-o-clock', 'w-5 h-5 text-[var(--ui-muted)]')
                    </div>
                    <p class="text-xs text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
