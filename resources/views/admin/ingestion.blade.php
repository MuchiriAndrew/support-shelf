@extends('layouts.app', ['pageTitle' => 'Ingestion', 'contentWidth' => 'max-w-[100rem]'])

@section('content')
    @if (session('ingestion_success'))
        <div class="mb-6 rounded-[22px] border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 text-sm font-medium text-emerald-200">
            {{ session('ingestion_success') }}
        </div>
    @endif

    @if (session('ingestion_error'))
        <div class="mb-6 rounded-[22px] border border-amber-500/20 bg-amber-500/10 px-5 py-4 text-sm font-medium text-amber-200">
            {{ session('ingestion_error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-[22px] border border-rose-500/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-200">
            <p class="font-semibold">Please fix the form fields below.</p>
            <ul class="mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="border-b border-[color:var(--border-soft)] pb-10 pt-10 sm:pb-12">
        <div class="grid gap-10 xl:grid-cols-[1.05fr_0.95fr]">
            <div class="max-w-4xl">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[var(--text-muted)]">Ingestion dashboard</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] text-[var(--text-primary)] sm:text-5xl">
                    Grow the support library behind every answer.
                </h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-[var(--text-secondary)]">
                    Add support sites, upload manuals and policies, and keep a clean view of the content that powers the assistant experience.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($statusCards as $card)
                    <article class="soft-card rounded-[24px] p-5">
                        <p class="text-sm text-[var(--text-muted)]">{{ $card['label'] }}</p>
                        <p class="mt-3 text-2xl font-semibold text-[var(--text-primary)]">{{ $card['value'] }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.24em] {{ str_contains(strtolower($card['status']), 'needs') ? 'text-amber-300' : 'text-emerald-300' }}">
                            {{ $card['status'] }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid gap-6 py-8 xl:grid-cols-2">
        <article class="surface-panel rounded-[30px] p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--text-muted)]">Add crawl source</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-[var(--text-primary)]">Register a support site</h2>
                </div>
                <span class="source-chip">{{ 'Website content' }}</span>
            </div>

            <form method="POST" action="{{ route('admin.ingestion.sources.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="source-name" class="text-sm font-medium text-[var(--text-primary)]">Name</label>
                    <input id="source-name" name="name" type="text" value="{{ old('name') }}" class="chat-form-input mt-2" placeholder="Acme Support Center">
                </div>

                <div>
                    <label for="source-url" class="text-sm font-medium text-[var(--text-primary)]">Start URL</label>
                    <input id="source-url" name="url" type="url" value="{{ old('url') }}" class="chat-form-input mt-2" placeholder="https://example.com/support">
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="content-selector" class="text-sm font-medium text-[var(--text-primary)]">Content selector</label>
                        <input id="content-selector" name="content_selector" type="text" value="{{ old('content_selector') }}" class="chat-form-input mt-2" placeholder="main, article">
                    </div>

                    <div>
                        <label for="max-depth" class="text-sm font-medium text-[var(--text-primary)]">Max depth</label>
                        <input id="max-depth" name="max_depth" type="number" min="0" max="5" value="{{ old('max_depth', 2) }}" class="chat-form-input mt-2">
                    </div>

                    <div>
                        <label for="max-pages" class="text-sm font-medium text-[var(--text-primary)]">Max pages</label>
                        <input id="max-pages" name="max_pages" type="number" min="1" max="500" value="{{ old('max_pages', config('crawling.max_pages', 40)) }}" class="chat-form-input mt-2">
                    </div>
                </div>

                <button type="submit" class="chat-primary-button">
                    Save source
                </button>
            </form>
        </article>

        <article class="surface-panel rounded-[30px] p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--text-muted)]">Import document</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-[var(--text-primary)]">Upload a manual or policy file</h2>
                </div>
                <span class="source-chip">{{ 'PDF, text, HTML' }}</span>
            </div>

            <form method="POST" action="{{ route('admin.ingestion.documents.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="document-file" class="text-sm font-medium text-[var(--text-primary)]">Document file</label>
                    <input id="document-file" name="file" type="file" class="chat-file-input mt-2">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="document-title" class="text-sm font-medium text-[var(--text-primary)]">Title</label>
                        <input id="document-title" name="title" type="text" value="{{ old('title') }}" class="chat-form-input mt-2" placeholder="ViewPort 27 User Guide">
                    </div>

                    <div>
                        <label for="source-name-upload" class="text-sm font-medium text-[var(--text-primary)]">Source group</label>
                        <input id="source-name-upload" name="source_name" type="text" value="{{ old('source_name') }}" class="chat-form-input mt-2" placeholder="Policy docs">
                    </div>
                </div>

                <div>
                    <label for="document-type" class="text-sm font-medium text-[var(--text-primary)]">Document type</label>
                    <input id="document-type" name="document_type" type="text" value="{{ old('document_type') }}" class="chat-form-input mt-2" placeholder="manual_pdf or return_policy">
                </div>

                <button type="submit" class="chat-primary-button">
                    Import document
                </button>
            </form>
        </article>
    </section>

    <section class="grid gap-6 pb-12 xl:grid-cols-3">
        <article class="soft-card rounded-[28px] p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--text-muted)]">Sources</p>
                    <h2 class="mt-2 text-xl font-semibold text-[var(--text-primary)]">Recent support sites</h2>
                </div>
                <span class="source-chip">{{ $recentSources->count() }}</span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($recentSources as $source)
                    <article class="rounded-[22px] border border-[color:var(--border-soft)] bg-[color:var(--surface-muted)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[var(--text-primary)]">{{ $source->name }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.22em] text-[var(--text-muted)]">{{ $source->source_type }}</p>
                            </div>
                            <span class="source-chip">{{ $source->documents_count }} docs</span>
                        </div>
                        @if ($source->url)
                            <p class="mt-3 break-all text-sm leading-6 text-[var(--text-secondary)]">{{ $source->url }}</p>
                        @endif
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-[var(--text-muted)]">
                                {{ $source->last_crawled_at ? 'Last crawl '.$source->last_crawled_at->diffForHumans() : 'Not crawled yet' }}
                            </p>
                            @if ($source->crawl_enabled && $source->url)
                                <form method="POST" action="{{ route('admin.ingestion.sources.crawl', $source) }}">
                                    @csrf
                                    <button type="submit" class="chat-secondary-button">
                                        Crawl now
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="rounded-[22px] border border-dashed border-[color:var(--border-soft)] px-4 py-5 text-sm leading-6 text-[var(--text-secondary)]">
                        No sources registered yet. Add your first support site above to start building the library.
                    </p>
                @endforelse
            </div>
        </article>

        <article class="soft-card rounded-[28px] p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--text-muted)]">Documents</p>
                    <h2 class="mt-2 text-xl font-semibold text-[var(--text-primary)]">Recent uploads and imports</h2>
                </div>
                <span class="source-chip">{{ $recentDocuments->count() }}</span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($recentDocuments as $document)
                    <article class="rounded-[22px] border border-[color:var(--border-soft)] bg-[color:var(--surface-muted)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[var(--text-primary)]">{{ $document->title }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.22em] text-[var(--text-muted)]">{{ $document->document_type }}</p>
                            </div>
                            <span class="source-chip">{{ $document->chunks_count }} passages</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-[var(--text-secondary)]">
                            {{ $document->source?->name ?? 'Standalone upload' }}
                        </p>
                        <p class="mt-3 text-xs uppercase tracking-[0.2em] text-[var(--text-muted)]">
                            {{ $document->updated_at->diffForHumans() }}
                        </p>
                    </article>
                @empty
                    <p class="rounded-[22px] border border-dashed border-[color:var(--border-soft)] px-4 py-5 text-sm leading-6 text-[var(--text-secondary)]">
                        No documents yet. Upload a manual or import a policy file to populate the library.
                    </p>
                @endforelse
            </div>
        </article>

        <article class="soft-card rounded-[28px] p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--text-muted)]">Activity</p>
                    <h2 class="mt-2 text-xl font-semibold text-[var(--text-primary)]">Recent crawl history</h2>
                </div>
                <span class="source-chip">{{ $recentRuns->count() }}</span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($recentRuns as $run)
                    <article class="rounded-[22px] border border-[color:var(--border-soft)] bg-[color:var(--surface-muted)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[var(--text-primary)]">{{ $run->source?->name ?? 'Support source' }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.22em] text-[var(--text-muted)]">{{ $run->status }}</p>
                            </div>
                            <span class="source-chip">{{ $run->documents_created + $run->documents_updated }} changes</span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-[var(--text-secondary)]">
                            {{ $run->documents_created }} created · {{ $run->documents_updated }} updated · {{ $run->pages_visited }} pages visited
                        </p>

                        <p class="mt-3 text-xs uppercase tracking-[0.2em] text-[var(--text-muted)]">
                            {{ $run->created_at->diffForHumans() }}
                        </p>
                    </article>
                @empty
                    <p class="rounded-[22px] border border-dashed border-[color:var(--border-soft)] px-4 py-5 text-sm leading-6 text-[var(--text-secondary)]">
                        No crawl runs yet. Once a support site is registered, activity will appear here.
                    </p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
