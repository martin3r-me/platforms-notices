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

    <x-ui-page-container spacing="space-y-6">
        {{-- Toolbar --}}
        @can('update', $note)
            <div class="flex items-center justify-between bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-4">
                <div class="flex items-center gap-2">
                    <button 
                        type="button"
                        wire:click="toggleViewMode"
                        class="px-3 py-1.5 text-sm rounded-md border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-2"
                    >
                        @if($viewMode === 'split')
                            @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                            <span>Split</span>
                        @elseif($viewMode === 'edit')
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                            <span>Editor</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Vorschau</span>
                        @endif
                    </button>
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" wire:model.live="autoSave" class="rounded">
                        <span>Auto-Save</span>
                    </label>
                </div>
                <x-ui-button variant="primary" size="sm" wire:click="save">
                    <span class="inline-flex items-center gap-2">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span>Speichern</span>
                    </span>
                </x-ui-button>
            </div>
        @endcan

        {{-- Editor/Preview Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            @can('update', $note)
                <div 
                    x-data="{
                        viewMode: @entangle('viewMode'),
                        content: @entangle('content'),
                        preview: '',
                        updatePreview() {
                            this.preview = markdownToHtml(this.content);
                        }
                    }"
                    x-init="updatePreview(); $watch('content', () => updatePreview())"
                    class="h-[calc(100vh-300px)]"
                >
                    {{-- Split View --}}
                    <template x-if="viewMode === 'split'">
                        <div class="grid grid-cols-2 h-full divide-x divide-[var(--ui-border)]">
                            <div class="flex flex-col">
                                <div class="px-4 py-2 bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)] text-xs font-semibold text-[var(--ui-muted)] uppercase">
                                    Editor
                                </div>
                                <textarea 
                                    wire:model.live.debounce.500ms="content" 
                                    x-model="content"
                                    class="flex-1 w-full px-4 py-4 border-0 focus:ring-0 resize-none font-mono text-sm leading-relaxed"
                                    placeholder="# Markdown-Inhalt hier eingeben...&#10;&#10;## Überschrift&#10;&#10;**Fett** und *kursiv*&#10;&#10;- Liste&#10;- Punkt 2"
                                    style="min-height: 100%;"
                                ></textarea>
                            </div>
                            <div class="flex flex-col overflow-auto">
                                <div class="px-4 py-2 bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)] text-xs font-semibold text-[var(--ui-muted)] uppercase">
                                    Vorschau
                                </div>
                                <div class="flex-1 px-4 py-4 prose prose-sm max-w-none" x-html="preview"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Edit Only --}}
                    <template x-if="viewMode === 'edit'">
                        <div class="flex flex-col h-full">
                            <div class="px-4 py-2 bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)] text-xs font-semibold text-[var(--ui-muted)] uppercase">
                                Editor
                            </div>
                            <textarea 
                                wire:model.live.debounce.500ms="content" 
                                x-model="content"
                                class="flex-1 w-full px-4 py-4 border-0 focus:ring-0 resize-none font-mono text-sm leading-relaxed"
                                placeholder="# Markdown-Inhalt hier eingeben..."
                                style="min-height: 100%;"
                            ></textarea>
                        </div>
                    </template>

                    {{-- Preview Only --}}
                    <template x-if="viewMode === 'preview'">
                        <div class="flex flex-col h-full overflow-auto">
                            <div class="px-4 py-2 bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)] text-xs font-semibold text-[var(--ui-muted)] uppercase">
                                Vorschau
                            </div>
                            <div class="flex-1 px-4 py-4 prose prose-sm max-w-none" x-html="preview"></div>
                        </div>
                    </template>
                </div>
            @else
                {{-- Read-only View --}}
                <div class="p-6 lg:p-8">
                    <div class="prose max-w-none">
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

                {{-- Quick Actions --}}
                @can('update', $note)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                        <div class="space-y-2">
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="save" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-check','w-4 h-4')
                                    <span>Speichern</span>
                                </span>
                            </x-ui-button>
                        </div>
                    </div>
                @endcan
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

    @push('scripts')
    <script>
        // Markdown zu HTML konvertieren
        function markdownToHtml(md) {
            if (!md) return '';
            
            let html = md;
            
            // Code-Blöcke (```)
            html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            
            // Inline Code (`)
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Überschriften
            html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
            html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
            html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
            
            // Fett und Kursiv
            html = html.replace(/\*\*\*(.*?)\*\*\*/gim, '<strong><em>$1</em></strong>');
            html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
            html = html.replace(/\*(.*?)\*/gim, '<em>$1</em>');
            
            // Links
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/gim, '<a href="$2" target="_blank" rel="noopener">$1</a>');
            
            // Listen
            html = html.replace(/^\- (.*$)/gim, '<li>$1</li>');
            html = html.replace(/^\+ (.*$)/gim, '<li>$1</li>');
            html = html.replace(/^\* (.*$)/gim, '<li>$1</li>');
            html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
            
            // Zeilenumbrüche
            html = html.replace(/\n\n/gim, '</p><p>');
            html = html.replace(/\n/gim, '<br>');
            
            // Paragraphen
            html = '<p>' + html + '</p>';
            html = html.replace(/<p><\/p>/gim, '');
            html = html.replace(/<p>(<h[1-6]>)/gim, '$1');
            html = html.replace(/(<\/h[1-6]>)<\/p>/gim, '$1');
            html = html.replace(/<p>(<ul>)/gim, '$1');
            html = html.replace(/(<\/ul>)<\/p>/gim, '$1');
            html = html.replace(/<p>(<pre>)/gim, '$1');
            html = html.replace(/(<\/pre>)<\/p>/gim, '$1');
            
            return html;
        }
    </script>
    @endpush
</x-ui-page>
