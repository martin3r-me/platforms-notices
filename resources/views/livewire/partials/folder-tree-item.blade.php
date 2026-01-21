@php
    $hasChildren = $folder->children()->exists();
    $isExpanded = in_array($folder->id, $this->expandedFolders);
    $paddingLeft = ($level * 1.5) + 0.75;
@endphp

<div class="folder-item" style="padding-left: {{ $paddingLeft }}rem;">
    <div class="flex items-center group">
        {{-- Expand/Collapse Button --}}
        @if($hasChildren)
            <button
                type="button"
                wire:click="toggleFolder({{ $folder->id }})"
                wire:loading.attr="disabled"
                class="flex-shrink-0 p-0.5 mr-1 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
            >
                @if($isExpanded)
                    @svg('heroicon-o-chevron-down', 'w-3 h-3')
                @else
                    @svg('heroicon-o-chevron-right', 'w-3 h-3')
                @endif
            </button>
        @else
            <span class="w-4 mr-1"></span>
        @endif

        {{-- Ordner-Icon und Name --}}
        <a 
            href="{{ route('notes.folders.show', ['notesFolder' => $folder]) }}"
            wire:navigate
            class="flex items-center flex-1 min-w-0 py-0.5 px-1.5 rounded-md hover:bg-[var(--ui-muted-5)] transition-colors"
        >
            @if($isExpanded)
                @svg('heroicon-o-folder-open', 'w-4 h-4 flex-shrink-0 text-[var(--ui-secondary)]')
            @else
                @svg('heroicon-o-folder', 'w-4 h-4 flex-shrink-0 text-[var(--ui-secondary)]')
            @endif
            <div class="flex-1 min-w-0 ml-1.5">
                <div class="truncate text-xs font-medium">{{ $folder->name }}</div>
                @if($folder->description)
                    <div class="truncate text-[10px] text-[var(--ui-muted)]">{{ mb_substr($folder->description, 0, 25) }}...</div>
                @endif
            </div>
        </a>
    </div>

    {{-- Unterordner (rekursiv) --}}
    @if($hasChildren && $isExpanded)
        @php
            $children = $folder->children()
                ->orderBy('name')
                ->get()
                ->filter(function($child) {
                    return auth()->user()->can('view', $child);
                });
        @endphp
        @foreach($children as $child)
            @include('notes::livewire.partials.folder-tree-item', [
                'folder' => $child,
                'level' => $level + 1
            ])
        @endforeach
    @endif
</div>
