<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$note->name" icon="heroicon-o-document-text">
            {{-- Breadcrumbs --}}
            <div class="flex items-center gap-2 text-sm text-[var(--ui-muted)] mt-1">
                @php($breadcrumbs = $this->getBreadcrumbs())
                @foreach($breadcrumbs as $index => $crumb)
                    @if($index > 0)
                        @svg('heroicon-o-chevron-right', 'w-3 h-3')
                    @endif
                    @if($index < count($breadcrumbs) - 1)
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
        @php($canUpdate = auth()->user()?->can('update', $note) ?? false)

        {{-- Debug (temporär): zeigt ob Edit-Branch aktiv ist & ob JS läuft --}}
        <div class="mb-3 text-xs text-[var(--ui-muted)] flex items-center gap-2">
            <span class="px-2 py-1 rounded border border-[var(--ui-border)] bg-[var(--ui-muted-5)]">
                canUpdate: <strong class="text-[var(--ui-secondary)]">{{ $canUpdate ? 'yes' : 'no' }}</strong>
            </span>
            <span class="px-2 py-1 rounded border border-[var(--ui-border)] bg-[var(--ui-muted-5)]">
                ToastUI: <strong class="text-[var(--ui-secondary)]" x-data x-text="(window.ToastUIEditor ? 'loaded' : 'missing')">…</strong>
            </span>
            <span class="px-2 py-1 rounded border border-[var(--ui-border)] bg-[var(--ui-muted-5)]">
                Alpine: <strong class="text-[var(--ui-secondary)]" x-data x-text="'ok'">…</strong>
            </span>
        </div>

        {{-- ToastUI Fallback Loader: wenn das Host-App-Bundle es nicht mitliefert --}}
        <script>
          (function () {
            if (window.ToastUIEditor) return;
            if (window.__toastui_loading) return;
            window.__toastui_loading = true;

            var cssHref = 'https://cdn.jsdelivr.net/npm/@toast-ui/editor@3.2.2/dist/toastui-editor.min.css';
            var jsSrc  = 'https://cdn.jsdelivr.net/npm/@toast-ui/editor@3.2.2/dist/toastui-editor-all.min.js';

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = cssHref;
            document.head.appendChild(link);

            var script = document.createElement('script');
            script.src = jsSrc;
            script.async = true;
            script.onload = function () {
              // UMD export
              if (!window.ToastUIEditor && window.toastui && window.toastui.Editor) {
                window.ToastUIEditor = window.toastui.Editor;
              }
              window.dispatchEvent(new CustomEvent('toastui:ready'));
            };
            document.head.appendChild(script);
          })();
        </script>

        @can('update', $note)
            {{-- Bear/Obsidian-like Editor --}}
            <div
                x-data="{
                    editor: null,
                    isSaving: false,
                    savedLabel: '—',
                    debounceTimer: null,
                    boot() {
                        const Editor = window.ToastUIEditor;
                        if (!Editor) return false;

                        if (this.editor && typeof this.editor.destroy === 'function') {
                            this.editor.destroy();
                        }

                        this.editor = new Editor({
                            el: this.$refs.editorEl,
                            height: '70vh',
                            initialEditType: 'wysiwyg',
                            previewStyle: 'tab', // no split
                            hideModeSwitch: true,
                            usageStatistics: false,
                            placeholder: 'Schreibe los…  😀  / Überschriften, Listen, Checklists, Links, Code',
                            toolbarItems: [
                                ['heading', 'bold', 'italic', 'strike'],
                                ['ul', 'ol', 'task', 'quote'],
                                ['link', 'code', 'codeblock', 'hr'],
                            ],
                            initialValue: @js($content ?? ''),
                        });

                        // Sync Editor -> Livewire state (debounced, ohne DB-write)
                        this.editor.on('change', () => {
                            const md = this.editor.getMarkdown();
                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                // beim Tippen keine Requests
                                $wire.set('content', md, false);
                                this.savedLabel = 'Ungespeichert';
                            }, 900);
                        });

                        // Ctrl/Cmd + S (nur einmal global; bei Navigation ersetzen)
                        if (window.__notesKeydownHandler) {
                            window.removeEventListener('keydown', window.__notesKeydownHandler);
                        }
                        window.__notesKeydownHandler = (e) => {
                            const isSave = (e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's';
                            if (!isSave) return;
                            e.preventDefault();
                            this.saveNow();
                        };
                        window.addEventListener('keydown', window.__notesKeydownHandler);

                        // Livewire events (wire:ignore)
                        const bindLivewire = () => {
                            if (!window.Livewire) return;
                            Livewire.on('notes-sync-editor', (payload) => {
                                if (!payload || payload.noteId !== {{ (int) $note->id }}) return;
                                if (typeof payload.name === 'string') {
                                    $wire.set('name', payload.name, false);
                                }
                                if (typeof payload.content === 'string' && this.editor) {
                                    this.editor.setMarkdown(payload.content);
                                }
                                this.savedLabel = '—';
                            });

                            Livewire.on('notes-saved', (payload) => {
                                if (!payload || payload.noteId !== {{ (int) $note->id }}) return;
                                this.savedLabel = 'Gespeichert';
                                this.isSaving = false;
                            });
                        };

                        if (window.Livewire) {
                            bindLivewire();
                        } else {
                            document.addEventListener('livewire:init', bindLivewire, { once: true });
                        }

                        return true;
                    },
                    init() {
                        if (!this.boot()) {
                            window.addEventListener('toastui:ready', () => this.boot(), { once: true });
                        }
                    },
                    saveNow() {
                        if (!this.editor) return;
                        this.isSaving = true;
                        const md = this.editor.getMarkdown();
                        $wire.set('content', md, false);
                        $wire.save();
                    },
                }"
                class="min-h-[calc(100vh-220px)]"
            >
                {{-- Title + tiny status --}}
                <div class="flex items-start justify-between gap-4 mb-6">
                    <input
                        type="text"
                        wire:model.defer="name"
                        placeholder="Titel…"
                        class="w-full text-4xl font-bold bg-transparent border-0 focus:ring-0 focus:outline-none text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)]"
                        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;"
                    />

                    <div class="flex items-center gap-3 flex-shrink-0 pt-2">
                        <div class="text-xs text-[var(--ui-muted)]">
                            <span x-text="savedLabel"></span>
                            <span class="mx-1">·</span>
                            <span>⌘S</span>
                        </div>
                        <button
                            type="button"
                            @click="saveNow()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors"
                        >
                            Speichern
                        </button>
                    </div>
                </div>

                <div class="notes-editor-shell">
                    <div wire:ignore x-ref="editorEl"></div>
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
        /* Toast UI Editor: make it feel like Bear/Obsidian (clean, minimal) */
        .notes-editor-shell .toastui-editor-defaultUI {
            border: 1px solid var(--ui-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .notes-editor-shell .toastui-editor-toolbar {
            background: color-mix(in srgb, var(--ui-muted-5) 70%, transparent);
            border-bottom: 1px solid var(--ui-border);
        }
        .notes-editor-shell .toastui-editor-contents {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 17px;
            line-height: 1.7;
        }
        .notes-editor-shell .toastui-editor-defaultUI-toolbar button {
            border-radius: 8px;
        }
        .notes-editor-shell .toastui-editor-mode-switch {
            display: none !important; /* ensure no split/mode switch UI */
        }

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
