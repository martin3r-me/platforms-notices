@php
    $hasChildren = $folder->children()->exists();
    $isExpanded = in_array($folder->id, $this->expandedFolders);
    $paddingLeft = ($level * 0.75) + 0.5; // Defensivere Einrückung: 0.75rem pro Level statt 1.5rem
@endphp

<div class="folder-item" style="padding-left: {{ $paddingLeft }}rem;">
    <div class="flex items-center group">
        {{-- Expand/Collapse Button --}}
        @if($hasChildren)
            <button
                type="button"
                wire:click="toggleFolder({{ $folder->id }})"
                wire:loading.attr="disabled"
                x-on:click="$wire.toggleFolder({{ $folder->id }}).then(() => {
                    const expanded = $wire.get('expandedFolders');
                    localStorage.setItem('notes.expandedFolders', JSON.stringify(expanded));
                })"
                class="flex-shrink-0 p-0.5 mr-0.5 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
            >
                @if($isExpanded)
                    @svg('heroicon-o-chevron-down', 'w-3 h-3')
                @else
                    @svg('heroicon-o-chevron-right', 'w-3 h-3')
                @endif
            </button>
        @else
            <span class="w-3.5 mr-0.5"></span>
        @endif

        {{-- Ordner-Icon und Name --}}
        <a 
            href="{{ route('notes.folders.show', ['notesFolder' => $folder]) }}"
            wire:navigate
            class="flex items-center flex-1 min-w-0 py-0.5 px-1 rounded-md hover:bg-[var(--ui-muted-5)] transition-colors"
        >
            @if($isExpanded)
                @svg('heroicon-o-folder-open', 'w-3.5 h-3.5 flex-shrink-0 text-[var(--ui-secondary)]')
            @else
                @svg('heroicon-o-folder', 'w-3.5 h-3.5 flex-shrink-0 text-[var(--ui-secondary)]')
            @endif
            <div class="flex-1 min-w-0 ml-1">
                <div class="truncate text-xs font-medium leading-tight">{{ $folder->name }}</div>
                @if($folder->description)
                    <div class="truncate text-[10px] text-[var(--ui-muted)] leading-tight mt-0.5">{{ mb_substr($folder->description, 0, 30) }}...</div>
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
