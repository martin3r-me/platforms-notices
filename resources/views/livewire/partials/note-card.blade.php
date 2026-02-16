@php
    $href = route('notes.notes.show', ['notesNote' => $note->id]);
    $excerpt = mb_substr(strip_tags($note->content ?? ''), 0, 180);
    $tags = $note->tags ?? [];
    $isGrid = ($viewMode ?? 'grid') === 'grid';
@endphp

@if($isGrid)
    {{-- Grid Card --}}
    <div class="group relative rounded-xl border border-[var(--ui-border)]/60 bg-[var(--ui-background)] hover:border-[var(--ui-primary)]/40 hover:shadow-md transition-all overflow-hidden">
        {{-- Pin Button --}}
        <button
            wire:click="togglePin('note', {{ $note->id }})"
            class="absolute top-2.5 right-2.5 p-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity {{ $note->is_pinned ? '!opacity-100 text-amber-400' : 'text-[var(--ui-muted)] hover:text-amber-400' }}"
            title="{{ $note->is_pinned ? 'Lösen' : 'Anpinnen' }}"
        >
            @if($note->is_pinned)
                @svg('heroicon-s-star', 'w-4 h-4')
            @else
                @svg('heroicon-o-star', 'w-4 h-4')
            @endif
        </button>

        <a href="{{ $href }}" wire:navigate class="block p-4">
            {{-- Title --}}
            <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-1.5 pr-6 line-clamp-1">
                {{ $note->name ?: 'Unbenannt' }}
            </h3>

            {{-- Excerpt --}}
            @if($excerpt)
                <p class="text-xs text-[var(--ui-muted)] line-clamp-3 mb-3 leading-relaxed">{{ $excerpt }}</p>
            @else
                <p class="text-xs text-[var(--ui-muted)] italic mb-3">Leer</p>
            @endif

            {{-- Footer --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    @foreach(array_slice($tags, 0, 2) as $tag)
                        <span class="inline-block px-1.5 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded text-[10px] font-medium">#{{ $tag }}</span>
                    @endforeach
                    @if(count($tags) > 2)
                        <span class="text-[10px] text-[var(--ui-muted)]">+{{ count($tags) - 2 }}</span>
                    @endif
                </div>
                <span class="text-[10px] text-[var(--ui-muted)]">{{ $note->updated_at->format('d.m.') }}</span>
            </div>
        </a>
    </div>
@else
    {{-- List Item --}}
    <div class="group relative flex items-center gap-3 p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-background)] hover:border-[var(--ui-primary)]/40 hover:bg-[var(--ui-muted-5)] transition-all">
        {{-- Pin --}}
        <button
            wire:click="togglePin('note', {{ $note->id }})"
            class="flex-shrink-0 p-1 rounded {{ $note->is_pinned ? 'text-amber-400' : 'text-[var(--ui-muted)] opacity-0 group-hover:opacity-100' }} transition-all"
        >
            @if($note->is_pinned)
                @svg('heroicon-s-star', 'w-3.5 h-3.5')
            @else
                @svg('heroicon-o-star', 'w-3.5 h-3.5')
            @endif
        </button>

        {{-- Icon --}}
        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-[var(--ui-muted-5)] flex items-center justify-center">
            @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-muted)]')
        </div>

        {{-- Content --}}
        <a href="{{ $href }}" wire:navigate class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $note->name ?: 'Unbenannt' }}</h3>
                @foreach(array_slice($tags, 0, 2) as $tag)
                    <span class="hidden sm:inline-block px-1.5 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded text-[10px] font-medium">#{{ $tag }}</span>
                @endforeach
            </div>
            @if($excerpt)
                <p class="text-xs text-[var(--ui-muted)] truncate mt-0.5">{{ $excerpt }}</p>
            @endif
        </a>

        {{-- Meta --}}
        <div class="flex-shrink-0 text-xs text-[var(--ui-muted)]">
            {{ $note->updated_at->format('d.m.Y') }}
        </div>
    </div>
@endif
