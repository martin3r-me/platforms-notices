@php
    $href = route('notes.folders.show', ['notesFolder' => $folder->id]);
    $noteCount = $folder->notes()->count();
    $childCount = $folder->children()->count();
    $totalItems = $noteCount + $childCount;
    $tags = $folder->tags ?? [];
    $isGrid = ($viewMode ?? 'grid') === 'grid';
@endphp

@if($isGrid)
    {{-- Grid Card --}}
    <div class="group relative rounded-xl border border-[var(--ui-border)]/60 bg-[var(--ui-background)] hover:border-[var(--ui-primary)]/40 hover:shadow-md transition-all overflow-hidden">
        {{-- Pin Button --}}
        <button
            wire:click="togglePin('folder', {{ $folder->id }})"
            class="absolute top-2.5 right-2.5 p-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity {{ $folder->is_pinned ? '!opacity-100 text-amber-400' : 'text-[var(--ui-muted)] hover:text-amber-400' }}"
            title="{{ $folder->is_pinned ? 'Lösen' : 'Anpinnen' }}"
        >
            @if($folder->is_pinned)
                @svg('heroicon-s-star', 'w-4 h-4')
            @else
                @svg('heroicon-o-star', 'w-4 h-4')
            @endif
        </button>

        <a href="{{ $href }}" wire:navigate class="block p-4">
            {{-- Folder Icon --}}
            <div class="w-10 h-10 rounded-xl bg-[var(--ui-primary-5)] flex items-center justify-center mb-3">
                @svg('heroicon-o-folder', 'w-5 h-5 text-[var(--ui-primary)]')
            </div>

            {{-- Title --}}
            <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-1 pr-6 line-clamp-1">
                {{ $folder->name }}
            </h3>

            {{-- Description --}}
            @if($folder->description)
                <p class="text-xs text-[var(--ui-muted)] line-clamp-2 mb-3">{{ $folder->description }}</p>
            @endif

            {{-- Footer --}}
            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center gap-1.5">
                    @foreach(array_slice($tags, 0, 2) as $tag)
                        <span class="inline-block px-1.5 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded text-[10px] font-medium">#{{ $tag }}</span>
                    @endforeach
                </div>
                <span class="text-[10px] text-[var(--ui-muted)]">{{ $totalItems }} {{ $totalItems === 1 ? 'Eintrag' : 'Einträge' }}</span>
            </div>
        </a>
    </div>
@else
    {{-- List Item --}}
    <div class="group relative flex items-center gap-3 p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-background)] hover:border-[var(--ui-primary)]/40 hover:bg-[var(--ui-muted-5)] transition-all">
        {{-- Pin --}}
        <button
            wire:click="togglePin('folder', {{ $folder->id }})"
            class="flex-shrink-0 p-1 rounded {{ $folder->is_pinned ? 'text-amber-400' : 'text-[var(--ui-muted)] opacity-0 group-hover:opacity-100' }} transition-all"
        >
            @if($folder->is_pinned)
                @svg('heroicon-s-star', 'w-3.5 h-3.5')
            @else
                @svg('heroicon-o-star', 'w-3.5 h-3.5')
            @endif
        </button>

        {{-- Icon --}}
        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-[var(--ui-primary-5)] flex items-center justify-center">
            @svg('heroicon-o-folder', 'w-4 h-4 text-[var(--ui-primary)]')
        </div>

        {{-- Content --}}
        <a href="{{ $href }}" wire:navigate class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $folder->name }}</h3>
                @foreach(array_slice($tags, 0, 2) as $tag)
                    <span class="hidden sm:inline-block px-1.5 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded text-[10px] font-medium">#{{ $tag }}</span>
                @endforeach
            </div>
            @if($folder->description)
                <p class="text-xs text-[var(--ui-muted)] truncate mt-0.5">{{ $folder->description }}</p>
            @endif
        </a>

        {{-- Meta --}}
        <div class="flex-shrink-0 text-xs text-[var(--ui-muted)]">
            {{ $totalItems }} {{ $totalItems === 1 ? 'Eintrag' : 'Einträge' }}
        </div>
    </div>
@endif
