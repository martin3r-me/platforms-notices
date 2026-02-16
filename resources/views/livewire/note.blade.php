<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="($note->name ?: 'Notiz')" icon="heroicon-o-document-text" />
    </x-slot>

    <x-ui-page-container class="max-w-4xl mx-auto">
        @can('update', $note)
            {{-- Modern Note Editor --}}
            <div
                x-data="{
                    editor: null,
                    isSaving: false,
                    savedLabel: '—',
                    debounceTimer: null,
                    showSlashMenu: false,
                    slashMenuItems: [
                        { label: 'Überschrift 1', icon: 'H1', action: 'heading1' },
                        { label: 'Überschrift 2', icon: 'H2', action: 'heading2' },
                        { label: 'Überschrift 3', icon: 'H3', action: 'heading3' },
                        { label: 'Checkliste', icon: '☑', action: 'task' },
                        { label: 'Aufzählung', icon: '•', action: 'ul' },
                        { label: 'Nummerierung', icon: '1.', action: 'ol' },
                        { label: 'Zitat', icon: '❝', action: 'quote' },
                        { label: 'Code-Block', icon: '<>', action: 'codeblock' },
                        { label: 'Trennlinie', icon: '—', action: 'hr' },
                        { label: 'Tabelle', icon: '▦', action: 'table' },
                        { label: 'Link', icon: '🔗', action: 'link' },
                        { label: 'Bild', icon: '🖼', action: 'image' },
                    ],
                    slashFilter: '',
                    slashSelectedIndex: 0,
                    filteredSlashItems() {
                        if (!this.slashFilter) return this.slashMenuItems;
                        const f = this.slashFilter.toLowerCase();
                        return this.slashMenuItems.filter(i => i.label.toLowerCase().includes(f));
                    },
                    boot() {
                        const Editor = window.ToastUIEditor;
                        if (!Editor) return false;

                        if (this.editor && typeof this.editor.destroy === 'function') {
                            this.editor.destroy();
                        }

                        this.editor = new Editor({
                            el: this.$refs.editorEl,
                            height: 'calc(100vh - 260px)',
                            minHeight: '400px',
                            initialEditType: 'wysiwyg',
                            previewStyle: 'tab',
                            hideModeSwitch: true,
                            usageStatistics: false,
                            placeholder: 'Schreibe los... Tippe / für Befehle',
                            toolbarItems: [
                                ['heading', 'bold', 'italic', 'strike'],
                                ['ul', 'ol', 'task', 'quote'],
                                ['table', 'image', 'link'],
                                ['code', 'codeblock', 'hr'],
                            ],
                            initialValue: @js($content ?? ''),
                        });

                        // Auto-save on change (debounced)
                        this.editor.on('change', () => {
                            const md = this.editor.getMarkdown();
                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                $wire.set('content', md, false);
                                this.savedLabel = 'Ungespeichert';
                            }, 900);
                        });

                        // Slash Commands support via keydown
                        const editorEl = this.$refs.editorEl;
                        const wysiwyg = editorEl?.querySelector('.toastui-editor-ww-container .ProseMirror') || editorEl?.querySelector('.toastui-editor-contents');

                        if (wysiwyg) {
                            wysiwyg.addEventListener('keydown', (e) => {
                                if (this.showSlashMenu) {
                                    const items = this.filteredSlashItems();
                                    if (e.key === 'ArrowDown') {
                                        e.preventDefault();
                                        this.slashSelectedIndex = (this.slashSelectedIndex + 1) % items.length;
                                    } else if (e.key === 'ArrowUp') {
                                        e.preventDefault();
                                        this.slashSelectedIndex = (this.slashSelectedIndex - 1 + items.length) % items.length;
                                    } else if (e.key === 'Enter') {
                                        e.preventDefault();
                                        if (items[this.slashSelectedIndex]) {
                                            this.executeSlashCommand(items[this.slashSelectedIndex].action);
                                        }
                                        this.showSlashMenu = false;
                                    } else if (e.key === 'Escape') {
                                        this.showSlashMenu = false;
                                    } else if (e.key === 'Backspace' && !this.slashFilter) {
                                        this.showSlashMenu = false;
                                    } else if (e.key.length === 1) {
                                        this.slashFilter += e.key;
                                        this.slashSelectedIndex = 0;
                                    }
                                    if (e.key === 'Backspace' && this.slashFilter) {
                                        this.slashFilter = this.slashFilter.slice(0, -1);
                                        this.slashSelectedIndex = 0;
                                    }
                                    return;
                                }
                            });

                            wysiwyg.addEventListener('input', (e) => {
                                if (!this.showSlashMenu) {
                                    const sel = window.getSelection();
                                    if (sel && sel.rangeCount > 0) {
                                        const range = sel.getRangeAt(0);
                                        const text = range.startContainer?.textContent || '';
                                        const offset = range.startOffset;
                                        if (offset > 0 && text[offset - 1] === '/') {
                                            const before = text[offset - 2] || '';
                                            if (!before || before === ' ' || before === '\n') {
                                                this.showSlashMenu = true;
                                                this.slashFilter = '';
                                                this.slashSelectedIndex = 0;
                                            }
                                        }
                                    }
                                }
                            });
                        }

                        // Ctrl/Cmd + S
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

                        // Livewire events
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
                        this.savedLabel = 'Speichert...';
                        const md = this.editor.getMarkdown();
                        $wire.set('content', md, false);
                        $wire.save();
                    },
                    executeSlashCommand(action) {
                        if (!this.editor) return;
                        // Remove the slash character
                        this.editor.exec(() => {
                            // Best effort: most commands work directly
                        });
                        switch(action) {
                            case 'heading1': this.editor.exec('heading', { level: 1 }); break;
                            case 'heading2': this.editor.exec('heading', { level: 2 }); break;
                            case 'heading3': this.editor.exec('heading', { level: 3 }); break;
                            case 'task': this.editor.exec('taskList'); break;
                            case 'ul': this.editor.exec('bulletList'); break;
                            case 'ol': this.editor.exec('orderedList'); break;
                            case 'quote': this.editor.exec('blockQuote'); break;
                            case 'codeblock': this.editor.exec('codeBlock'); break;
                            case 'hr': this.editor.exec('thematicBreak'); break;
                            case 'table': this.editor.exec('addTable', { rowCount: 3, columnCount: 3 }); break;
                            case 'link': this.editor.exec('addLink', { linkUrl: '', linkText: '' }); break;
                            case 'image': this.editor.exec('addImage', { imageUrl: '', altText: '' }); break;
                        }
                        this.showSlashMenu = false;
                        this.slashFilter = '';
                    },
                }"
                class="min-h-[calc(100vh-220px)]"
            >
                {{-- Title + Status Bar --}}
                <div class="flex items-start justify-between gap-4 mb-4">
                    <input
                        type="text"
                        wire:model.defer="name"
                        placeholder="Titel..."
                        class="w-full text-3xl md:text-4xl font-bold bg-transparent border-0 focus:ring-0 focus:outline-none text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)]/50"
                        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;"
                    />

                    <div class="flex items-center gap-2 flex-shrink-0 pt-2">
                        {{-- Save Status --}}
                        <div class="text-xs text-[var(--ui-muted)] flex items-center gap-1.5">
                            <span x-text="savedLabel"></span>
                            <span class="text-[var(--ui-border)]">·</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded text-[10px] font-mono">⌘S</kbd>
                        </div>

                        {{-- Pin --}}
                        <button
                            wire:click="togglePin"
                            class="p-1.5 rounded-lg hover:bg-[var(--ui-muted-5)] transition-colors {{ $note->is_pinned ? 'text-amber-400' : 'text-[var(--ui-muted)]' }}"
                            title="{{ $note->is_pinned ? 'Lösen' : 'Anpinnen' }}"
                        >
                            @if($note->is_pinned)
                                @svg('heroicon-s-star', 'w-4 h-4')
                            @else
                                @svg('heroicon-o-star', 'w-4 h-4')
                            @endif
                        </button>

                        {{-- PDF --}}
                        <a
                            href="{{ route('notes.notes.pdf', $note) }}"
                            class="p-1.5 rounded-lg hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-muted)]"
                            target="_blank"
                            rel="noopener"
                            title="PDF exportieren"
                        >
                            @svg('heroicon-o-document-arrow-down', 'w-4 h-4')
                        </a>

                        {{-- Save Button --}}
                        <button
                            type="button"
                            @click="saveNow()"
                            class="px-3 py-1.5 text-sm rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity font-medium"
                        >
                            Speichern
                        </button>
                    </div>
                </div>

                {{-- Tags Row --}}
                <div class="flex items-center gap-2 mb-4" x-data="{ showTagInput: false, newTag: '' }">
                    @foreach($note->tags ?? [] as $tag)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded-full text-xs font-medium">
                            #{{ $tag }}
                            <button wire:click="removeTag('{{ $tag }}')" class="hover:text-red-500 transition-colors">
                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                            </button>
                        </span>
                    @endforeach
                    <template x-if="showTagInput">
                        <input
                            type="text"
                            x-model="newTag"
                            @keydown.enter.prevent="if(newTag.trim()) { $wire.addTag(newTag.trim()); newTag = ''; showTagInput = false; }"
                            @keydown.escape="showTagInput = false; newTag = ''"
                            @blur="if(newTag.trim()) { $wire.addTag(newTag.trim()); } newTag = ''; showTagInput = false;"
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
                </div>

                {{-- Editor --}}
                <div class="notes-editor-shell relative">
                    <div wire:ignore x-ref="editorEl"></div>

                    {{-- Slash Command Menu --}}
                    <div
                        x-show="showSlashMenu"
                        x-cloak
                        @click.away="showSlashMenu = false"
                        class="absolute z-50 mt-1 w-64 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded-xl shadow-xl overflow-hidden"
                        style="top: 50%; left: 2rem;"
                    >
                        <div class="p-1.5">
                            <div class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                Befehle
                            </div>
                            <template x-for="(item, index) in filteredSlashItems()" :key="item.action">
                                <button
                                    @click="executeSlashCommand(item.action)"
                                    @mouseenter="slashSelectedIndex = index"
                                    :class="{ 'bg-[var(--ui-primary-5)]': slashSelectedIndex === index }"
                                    class="w-full flex items-center gap-3 px-2 py-1.5 rounded-lg text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors cursor-pointer"
                                >
                                    <span class="w-7 h-7 flex items-center justify-center rounded-md bg-[var(--ui-muted-5)] text-xs font-mono font-bold" x-text="item.icon"></span>
                                    <span x-text="item.label"></span>
                                </button>
                            </template>
                            <div x-show="filteredSlashItems().length === 0" class="px-2 py-3 text-center text-xs text-[var(--ui-muted)]">
                                Kein Befehl gefunden
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Read-only View --}}
            <div class="space-y-6 prose prose-lg max-w-none">
                <h1 class="text-3xl md:text-4xl font-bold text-[var(--ui-secondary)] mb-4" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;">{{ $note->name }}</h1>

                {{-- Tags --}}
                @if($note->tags && count($note->tags) > 0)
                    <div class="flex items-center gap-2 not-prose">
                        @foreach($note->tags as $tag)
                            <span class="px-2 py-0.5 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded-full text-xs font-medium">#{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="markdown-content">
                    {!! \Illuminate\Support\Str::markdown($note->content ?? '') !!}
                </div>
            </div>
        @endcan
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Details" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Folder Navigation --}}
                @if($note->folder)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Ordner</h3>
                        <a
                            href="{{ route('notes.folders.show', $note->folder) }}"
                            wire:navigate
                            class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/20 hover:bg-[var(--ui-primary-10)] transition-colors"
                        >
                            @svg('heroicon-o-folder', 'w-4 h-4 text-[var(--ui-primary)]')
                            <span class="text-xs font-medium text-[var(--ui-primary)]">{{ $note->folder->name }}</span>
                        </a>
                    </div>
                @endif

                {{-- Sharing --}}
                @can('update', $note)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Teilen</h3>
                        <div class="space-y-2">
                            {{-- Existing Shares --}}
                            @foreach($shares as $share)
                                <div class="flex items-center justify-between px-2 py-1.5 bg-[var(--ui-muted-5)] rounded-lg">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-6 h-6 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center flex-shrink-0">
                                            <span class="text-[10px] font-bold text-[var(--ui-primary)]">{{ mb_substr($share->user->name ?? '?', 0, 1) }}</span>
                                        </div>
                                        <span class="text-xs text-[var(--ui-secondary)] truncate">{{ $share->user->name ?? 'Unbekannt' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <select
                                            wire:change="updateSharePermission({{ $share->user_id }}, $event.target.value)"
                                            class="text-[10px] px-1 py-0.5 rounded border border-[var(--ui-border)] bg-[var(--ui-background)] text-[var(--ui-secondary)] focus:outline-none"
                                        >
                                            <option value="view" @if($share->permission === 'view') selected @endif>Lesen</option>
                                            <option value="edit" @if($share->permission === 'edit') selected @endif>Bearbeiten</option>
                                        </select>
                                        <button wire:click="removeShare({{ $share->user_id }})" class="p-0.5 text-red-400 hover:text-red-600">
                                            @svg('heroicon-o-x-mark', 'w-3 h-3')
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Add Share --}}
                            <div class="flex gap-1.5">
                                <select wire:model.live="shareUserId" class="flex-1 px-2 py-1 text-xs rounded-lg border border-[var(--ui-border)] bg-[var(--ui-muted-5)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]">
                                    <option value="">Teilen mit...</option>
                                    @foreach($teamUsers as $teamUser)
                                        @if($teamUser->id !== auth()->id() && !$shares->contains('user_id', $teamUser->id))
                                            <option value="{{ $teamUser->id }}">{{ $teamUser->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button
                                    wire:click="addShare"
                                    class="px-2 py-1 text-xs rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] hover:opacity-90 transition-opacity disabled:opacity-50"
                                    @if(!$shareUserId) disabled @endif
                                >
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                </button>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Note Details --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Details</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">{{ $note->created_at->format('d.m.Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Aktualisiert</span>
                            <span class="text-xs text-[var(--ui-secondary)] font-medium">{{ $note->updated_at->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($note->done)
                            <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">Status</span>
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-[var(--ui-success-5)] text-[var(--ui-success)]">Erledigt</span>
                            </div>
                        @endif
                        @if($note->user)
                            <div class="flex justify-between items-center py-1.5 px-2.5 bg-[var(--ui-muted-5)] rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">Autor</span>
                                <span class="text-xs text-[var(--ui-secondary)] font-medium">{{ $note->user->name ?? '—' }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Delete --}}
                @can('delete', $note)
                    <div>
                        <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Aktionen</h3>
                        <button
                            wire:click="deleteNote"
                            wire:confirm="Möchtest du diese Notiz wirklich löschen?"
                            class="w-full px-3 py-1.5 text-xs rounded-lg border border-red-500/30 bg-red-500/5 text-red-500 hover:bg-red-500/10 transition-colors flex items-center justify-center gap-1.5"
                        >
                            @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                            Notiz löschen
                        </button>
                    </div>
                @endcan

                {{-- Keyboard Shortcuts --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">Tastenkürzel</h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Speichern</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">⌘S</kbd>
                        </div>
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Fett</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">⌘B</kbd>
                        </div>
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Kursiv</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">⌘I</kbd>
                        </div>
                        <div class="flex items-center justify-between py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                            <span class="text-[var(--ui-muted)]">Slash-Befehle</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-background)] border border-[var(--ui-border)] rounded text-[10px] font-mono">/</kbd>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    @push('styles')
    <style>
        /* Toast UI Editor: z-index Isolation */
        .notes-editor-shell {
            position: relative;
            z-index: 1 !important;
            isolation: isolate;
        }
        .notes-editor-shell .toastui-editor-defaultUI {
            border: 1px solid var(--ui-border);
            border-radius: 12px;
            overflow: hidden;
            z-index: auto !important;
        }
        .notes-editor-shell .toastui-editor-toolbar {
            background: color-mix(in srgb, var(--ui-muted-5) 70%, transparent);
            border-bottom: 1px solid var(--ui-border);
            z-index: auto !important;
            padding: 4px 8px;
        }
        .notes-editor-shell .toastui-editor-popup,
        .notes-editor-shell .toastui-editor-dropdown,
        .notes-editor-shell [class*="toastui-editor"] {
            z-index: auto !important;
        }
        .notes-editor-shell .toastui-editor-popup {
            z-index: 5 !important;
        }
        .notes-editor-shell .toastui-editor-contents {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.75;
            padding: 1.5rem;
        }
        .notes-editor-shell .toastui-editor-defaultUI-toolbar button {
            border-radius: 8px;
        }
        .notes-editor-shell .toastui-editor-mode-switch {
            display: none !important;
        }

        /* Enhanced Checklist Styles */
        .notes-editor-shell .toastui-editor-contents ul.task-list-item,
        .notes-editor-shell .toastui-editor-contents li.task-list-item {
            list-style: none;
            padding-left: 0;
        }
        .notes-editor-shell .toastui-editor-contents input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid var(--ui-border);
            margin-right: 8px;
            cursor: pointer;
            accent-color: var(--ui-primary);
        }

        /* Table Styles */
        .notes-editor-shell .toastui-editor-contents table {
            border-collapse: collapse;
            width: 100%;
            margin: 1em 0;
        }
        .notes-editor-shell .toastui-editor-contents table th,
        .notes-editor-shell .toastui-editor-contents table td {
            border: 1px solid var(--ui-border);
            padding: 8px 12px;
            text-align: left;
        }
        .notes-editor-shell .toastui-editor-contents table th {
            background: var(--ui-muted-5);
            font-weight: 600;
        }

        /* Read-only Markdown Rendering */
        .markdown-content {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.75;
            color: var(--ui-secondary);
        }
        .markdown-content h1 { font-size: 2em; font-weight: 700; margin: 1.5em 0 0.5em; }
        .markdown-content h2 { font-size: 1.5em; font-weight: 600; margin: 1.3em 0 0.5em; }
        .markdown-content h3 { font-size: 1.25em; font-weight: 600; margin: 1.2em 0 0.5em; }
        .markdown-content p { margin-bottom: 1em; }
        .markdown-content ul, .markdown-content ol { margin-bottom: 1em; padding-left: 1.5em; }
        .markdown-content li { margin-bottom: 0.5em; }
        .markdown-content strong { font-weight: 600; }
        .markdown-content em { font-style: italic; }
        .markdown-content code {
            background: var(--ui-muted-5);
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', Consolas, monospace;
            font-size: 0.9em;
        }
        .markdown-content pre {
            background: var(--ui-muted-5);
            padding: 1em;
            border-radius: 8px;
            overflow-x: auto;
            margin-bottom: 1em;
        }
        .markdown-content pre code { background: transparent; padding: 0; }
        .markdown-content a { color: var(--ui-primary); text-decoration: underline; }
        .markdown-content a:hover { text-decoration: none; }
        .markdown-content blockquote {
            border-left: 3px solid var(--ui-primary);
            padding-left: 1em;
            margin-left: 0;
            color: var(--ui-muted);
            font-style: italic;
        }
        .markdown-content hr { border: none; border-top: 1px solid var(--ui-border); margin: 2em 0; }
        .markdown-content table { border-collapse: collapse; width: 100%; margin: 1em 0; }
        .markdown-content table th, .markdown-content table td {
            border: 1px solid var(--ui-border);
            padding: 8px 12px;
        }
        .markdown-content table th { background: var(--ui-muted-5); font-weight: 600; }
        .markdown-content img { max-width: 100%; border-radius: 8px; margin: 1em 0; }

        /* Mobile Optimization */
        @media (max-width: 640px) {
            .notes-editor-shell .toastui-editor-contents {
                font-size: 16px;
                padding: 1rem;
                -webkit-text-size-adjust: 100%;
            }
            .notes-editor-shell .toastui-editor-toolbar {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
        }
    </style>
    @endpush
</x-ui-page>
