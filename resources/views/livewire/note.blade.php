<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$note->name" icon="heroicon-o-document-text">
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

    <x-ui-page-container class="max-w-4xl mx-auto">
        <div 
            x-data="{
                content: @entangle('content'),
                name: @entangle('name'),
                isEditing: false,
                init() {
                    // Auto-focus beim Laden
                    this.$nextTick(() => {
                        const editor = this.$refs.editor;
                        if (editor && @can('update', $note)) {
                            editor.focus();
                        }
                    });
                }
            }"
            class="min-h-[calc(100vh-200px)]"
        >
            @can('update', $note)
                {{-- Editor Mode --}}
                <div class="space-y-6">
                    {{-- Title Editor --}}
                    <div>
                        <input 
                            type="text"
                            wire:model.live.debounce.500ms="name"
                            x-model="name"
                            placeholder="Titel der Notiz..."
                            class="w-full text-4xl font-bold bg-transparent border-0 focus:ring-0 focus:outline-none text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)] resize-none"
                            style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;"
                        />
                    </div>

                    {{-- Content Editor --}}
                    <div class="relative">
                        <textarea 
                            x-ref="editor"
                            wire:model.live.debounce.500ms="content"
                            x-model="content"
                            placeholder="# Überschrift&#10;&#10;Schreibe deine Notiz hier...&#10;&#10;- Liste&#10;- Punkt 2&#10;&#10;**Fett** und *kursiv*&#10;&#10;😀 Emojis funktionieren auch!"
                            class="w-full min-h-[600px] p-0 bg-transparent border-0 focus:ring-0 focus:outline-none text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)] resize-none leading-relaxed"
                            style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; font-size: 17px; line-height: 1.7;"
                        ></textarea>
                    </div>
                </div>
            @else
                {{-- Read-only View --}}
                <div class="space-y-6 prose prose-lg max-w-none">
                    <h1 class="text-4xl font-bold text-[var(--ui-secondary)] mb-8">{{ $note->name }}</h1>
                    <div class="markdown-content">
                        {!! \Illuminate\Support\Str::markdown($note->content ?? '') !!}
                    </div>
                </div>
            @endcan
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Navigation" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Ordner-Navigation --}}
                @if($note->folder)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Ordner</h3>
                        <a 
                            href="{{ route('notes.folders.show', $note->folder) }}" 
                            wire:navigate
                            class="flex items-center gap-2 px-3 py-2 rounded-md bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/20 hover:bg-[var(--ui-primary-10)] transition-colors"
                        >
                            @svg('heroicon-o-folder', 'w-4 h-4 text-[var(--ui-primary)]')
                            <span class="text-sm font-medium text-[var(--ui-primary)]">{{ $note->folder->name }}</span>
                        </a>
                    </div>
                @endif

                {{-- Notiz-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $note->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-md">
                            <span class="text-sm text-[var(--ui-muted)]">Aktualisiert</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $note->updated_at->format('d.m.Y H:i') }}
                            </span>
                        </div>
                        @if($note->done)
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

    @push('styles')
    <style>
        /* Obsidian/Bear Style Markdown Rendering */
        .markdown-content {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 17px;
            line-height: 1.7;
            color: var(--ui-secondary);
        }
        
        .markdown-content h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            line-height: 1.2;
        }
        
        .markdown-content h2 {
            font-size: 2em;
            font-weight: 600;
            margin-top: 1.3em;
            margin-bottom: 0.5em;
            line-height: 1.3;
        }
        
        .markdown-content h3 {
            font-size: 1.5em;
            font-weight: 600;
            margin-top: 1.2em;
            margin-bottom: 0.5em;
        }
        
        .markdown-content h4 {
            font-size: 1.25em;
            font-weight: 600;
            margin-top: 1em;
            margin-bottom: 0.5em;
        }
        
        .markdown-content p {
            margin-bottom: 1em;
        }
        
        .markdown-content ul,
        .markdown-content ol {
            margin-bottom: 1em;
            padding-left: 1.5em;
        }
        
        .markdown-content li {
            margin-bottom: 0.5em;
        }
        
        .markdown-content strong {
            font-weight: 600;
        }
        
        .markdown-content em {
            font-style: italic;
        }
        
        .markdown-content code {
            background: var(--ui-muted-5);
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .markdown-content pre {
            background: var(--ui-muted-5);
            padding: 1em;
            border-radius: 8px;
            overflow-x: auto;
            margin-bottom: 1em;
        }
        
        .markdown-content pre code {
            background: transparent;
            padding: 0;
        }
        
        .markdown-content a {
            color: var(--ui-primary);
            text-decoration: underline;
        }
        
        .markdown-content a:hover {
            text-decoration: none;
        }
        
        .markdown-content blockquote {
            border-left: 3px solid var(--ui-primary);
            padding-left: 1em;
            margin-left: 0;
            color: var(--ui-muted);
            font-style: italic;
        }
        
        .markdown-content hr {
            border: none;
            border-top: 1px solid var(--ui-border);
            margin: 2em 0;
        }
        
        /* Emoji Support */
        .markdown-content {
            font-variant-emoji: emoji;
        }
    </style>
    @endpush
</x-ui-page>
