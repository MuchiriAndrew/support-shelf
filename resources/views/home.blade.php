@extends('layouts.app', ['pageTitle' => 'Overview', 'contentWidth' => 'max-w-[90rem]'])

@section('content')
    @php
        $primaryButtonClass = 'inline-flex items-center gap-2 rounded-full bg-[var(--button-primary-bg)] px-6 py-3 text-sm font-semibold text-[var(--button-primary-text)] shadow-[var(--shadow-card)] transition hover:-translate-y-0.5 hover:opacity-95';
        $secondaryButtonClass = 'inline-flex items-center gap-2 rounded-full border border-[color:var(--button-secondary-border)] bg-[var(--button-secondary-bg)] px-6 py-3 text-sm font-semibold text-[var(--text-primary)] transition hover:border-[color:var(--border-soft)] hover:bg-[var(--page-bg-strong)]';
        $panelClass = 'rounded-[1.7rem] border border-[color:var(--border-soft)] bg-[var(--surface-elevated)] p-5 shadow-[var(--shadow-soft)]';
        $sectionHeadingClass = 'mt-3 max-w-[13em] text-[clamp(2rem,4vw,3.4rem)] font-semibold leading-[1.02] tracking-[-0.05em] text-[var(--text-primary)]';
        $kickerClass = 'text-[0.78rem] font-bold uppercase tracking-[0.24em] text-[var(--text-muted)]';
        $panelKickerClass = 'text-[0.76rem] font-bold uppercase tracking-[0.18em] text-[var(--brand-primary)]';
        $panelTitleClass = 'mt-3 text-[clamp(1.35rem,2vw,1.8rem)] font-semibold leading-[1.12] tracking-[-0.04em] text-[var(--text-primary)]';
        $copyClass = 'mt-2 text-[0.98rem] leading-[1.8] text-[var(--text-secondary)] max-sm:text-[0.95rem] max-sm:leading-[1.72]';
    @endphp

    <section class="relative overflow-hidden py-12 sm:py-16 lg:py-[4.5rem]">
        {{-- <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-16 top-[-5rem] h-72 w-72 rounded-full bg-blue-500/25 blur-[70px] animate-[landing-float_14s_ease-in-out_infinite]"></div>
            <div class="absolute right-[5%] top-8 h-56 w-56 rounded-full bg-violet-500/20 blur-[70px] animate-[landing-float_14s_ease-in-out_infinite] [animation-delay:-5s]"></div>
            <div class="absolute inset-0 opacity-45 [background-image:linear-gradient(to_right,color-mix(in_srgb,var(--border-soft)_70%,transparent)_1px,transparent_1px),linear-gradient(to_bottom,color-mix(in_srgb,var(--border-soft)_70%,transparent)_1px,transparent_1px)] [background-size:4.5rem_4.5rem] [mask-image:radial-gradient(circle_at_center,black,transparent_75%)]"></div>
        </div> --}}

        <div class="relative z-10 grid items-center gap-10 xl:grid-cols-[minmax(0,1.08fr)_minmax(22rem,0.92fr)] xl:gap-13">
            <div class="relative z-10 max-w-4xl">
                <p class="{{ $kickerClass }}">{{ $hero['kicker'] ?? 'Private assistant platform' }}</p>
                <h1 class="mt-4 max-w-[6.5em] text-[clamp(3rem,7vw,5.9rem)] font-semibold leading-[0.95] tracking-[-0.06em] text-[var(--text-primary)] max-sm:max-w-[9em] max-sm:text-[clamp(2.7rem,13vw,3.8rem)]">
                    {{ $hero['title'] ?? 'Turn your documents and websites into an assistant that knows your world.' }}
                </h1>
                <p class="mt-6 max-w-[42rem] text-[1.08rem] leading-[1.9] text-[var(--text-secondary)] max-sm:text-[0.95rem] max-sm:leading-[1.72]">
                    {{ $hero['description'] ?? 'SupportShelf gives every user a private workspace for source ingestion, semantic retrieval, and grounded conversations, all wrapped in a polished product experience.' }}
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    @auth
                        <a href="{{ route('chat') }}" class="{{ $primaryButtonClass }}">
                            Open your assistant
                        </a>
                        <a href="{{ route('filament.admin.pages.knowledge-ingestion') }}" class="{{ $secondaryButtonClass }}">
                            Add knowledge
                        </a>
                        <a href="{{ route('filament.admin.pages.assistant-settings') }}" class="{{ $secondaryButtonClass }}">
                            Tune your assistant
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="{{ $primaryButtonClass }}">
                            Create your workspace
                        </a>
                        <a href="{{ route('login') }}" class="{{ $secondaryButtonClass }}">
                            Log in
                        </a>
                    @endauth
                </div>

                <div class="mt-7 grid gap-4 sm:grid-cols-3">
                    @foreach ($heroMetrics as $metric)
                        <div class="rounded-[1.35rem] border border-[color:var(--border-soft)] bg-[var(--surface)] px-4 py-4 shadow-[var(--shadow-soft)] backdrop-blur-xl">
                            <p class="text-[0.8rem] font-bold uppercase tracking-[0.14em] text-[var(--text-muted)]">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-[0.98rem] text-[var(--text-primary)]">{{ $metric['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4">
                <div class="relative overflow-hidden rounded-[1.7rem] border border-[color:var(--border-soft)] bg-[var(--surface-elevated)] p-5 shadow-[var(--shadow-card)] before:absolute before:inset-x-0 before:bottom-0 before:h-px before:bg-[linear-gradient(90deg,transparent,color-mix(in_srgb,var(--brand-primary)_60%,transparent),transparent)]">
                    <div class="absolute right-0 top-0 h-48 w-48 rounded-full bg-blue-500/15 blur-[80px]"></div>
                    <div class="relative">
                        <p class="{{ $panelKickerClass }}">Workspace snapshot</p>
                        <h2 class="{{ $panelTitleClass }}">Each user gets a fully isolated assistant environment.</h2>
                        <div class="mt-5 grid gap-4">
                            <div class="flex items-start gap-3 rounded-[1.15rem] border border-[color:var(--border-soft)] bg-[var(--surface)] px-4 py-4">
                                <span class="mt-1 inline-flex h-[0.65rem] w-[0.65rem] rounded-full bg-[var(--brand-primary)] shadow-[0_0_0_0.35rem_color-mix(in_srgb,var(--brand-primary)_15%,transparent)]"></span>
                                <div>
                                    <p class="text-[0.96rem] font-semibold text-[var(--text-primary)]">Website ingestion</p>
                                    <p class="mt-1 text-[0.98rem] leading-[1.8] text-[var(--text-secondary)]">Crawl entire sites into a user-owned knowledge base.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-[1.15rem] border border-[color:var(--border-soft)] bg-[var(--surface)] px-4 py-4">
                                <span class="mt-1 inline-flex h-[0.65rem] w-[0.65rem] rounded-full bg-[var(--brand-primary)] shadow-[0_0_0_0.35rem_color-mix(in_srgb,var(--brand-primary)_15%,transparent)]"></span>
                                <div>
                                    <p class="text-[0.96rem] font-semibold text-[var(--text-primary)]">Private retrieval</p>
                                    <p class="mt-1 text-[0.98rem] leading-[1.8] text-[var(--text-secondary)]">Vectors, search, and responses remain scoped to the current account.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 rounded-[1.15rem] border border-[color:var(--border-soft)] bg-[var(--surface)] px-4 py-4">
                                <span class="mt-1 inline-flex h-[0.65rem] w-[0.65rem] rounded-full bg-[var(--brand-primary)] shadow-[0_0_0_0.35rem_color-mix(in_srgb,var(--brand-primary)_15%,transparent)]"></span>
                                <div>
                                    <p class="text-[0.96rem] font-semibold text-[var(--text-primary)]">Configurable behavior</p>
                                    <p class="mt-1 text-[0.98rem] leading-[1.8] text-[var(--text-secondary)]">Name the assistant and define instructions from the admin panel.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.7rem] border border-[color:var(--border-soft)] bg-[var(--surface)] p-5 shadow-[var(--shadow-card)]">
                    <div class="rounded-[1.25rem] border border-[color:var(--border-soft)] bg-[var(--surface)] px-4 py-4">
                        <p class="inline-flex items-center rounded-full bg-[var(--button-secondary-bg)] px-3 py-1.5 text-[0.8rem] font-semibold text-[var(--text-primary)]">Assistant response</p>
                        <p class="mt-3 text-[0.98rem] leading-[1.8] text-[var(--text-secondary)]">
                            "I found the relevant details in your uploaded material and website content. Here's the answer based on your private knowledge."
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-8">
        <div class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)] lg:items-end">
            <div>
                <p class="{{ $kickerClass }}">Why it works</p>
                <h2 class="{{ $sectionHeadingClass }}">A modern assistant product with the right SaaS boundaries.</h2>
            </div>
            <p class="text-[0.98rem] leading-[1.8] text-[var(--text-secondary)] max-sm:text-[0.95rem] max-sm:leading-[1.72]">
                The platform is built around per-user knowledge ownership, fast ingestion, and a chat experience that feels polished from the first message.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($pillars as $pillar)
                <article class="{{ $panelClass }}">
                    <p class="{{ $panelKickerClass }}">Platform value</p>
                    <h3 class="{{ $panelTitleClass }}">{{ $pillar['title'] }}</h3>
                    <p class="{{ $copyClass }}">{{ $pillar['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="py-8">
        <div class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)] lg:items-end">
            <div>
                <p class="{{ $kickerClass }}">How it flows</p>
                <h2 class="{{ $sectionHeadingClass }}">From raw content to an assistant that answers from private context.</h2>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($workflow as $index => $step)
                <article class="{{ $panelClass }}">
                    <p class="text-[0.8rem] font-bold uppercase tracking-[0.16em] text-[var(--text-muted)]">0{{ $index + 1 }}</p>
                    <h3 class="{{ $panelTitleClass }}">{{ $step['title'] }}</h3>
                    <p class="{{ $copyClass }}">{{ $step['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="py-8">
        <div class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.72fr)] lg:items-end">
            <div>
                <p class="{{ $kickerClass }}">Product walkthroughs</p>
                <h2 class="{{ $sectionHeadingClass }}">Add your own recordings to show the full product story.</h2>
            </div>
            <p class="text-[0.98rem] leading-[1.8] text-[var(--text-secondary)] max-sm:text-[0.95rem] max-sm:leading-[1.72]">
                These sections are ready for screen recordings of ingestion and live chat, so the landing page can double as a product showcase.
            </p>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($showcases as $showcase)
                <article class="{{ $panelClass }} flex flex-col">
                    <p class="{{ $panelKickerClass }}">{{ $showcase['eyebrow'] }}</p>
                    <h3 class="{{ $panelTitleClass }}">{{ $showcase['title'] }}</h3>
                    <p class="{{ $copyClass }}">{{ $showcase['description'] }}</p>

                    @if ($showcase['video_src'])
                        <video class="mt-5 aspect-[16/10] w-full overflow-hidden rounded-[1.35rem] border border-[color:var(--border-soft)] bg-[var(--page-bg-strong)] object-cover" controls playsinline preload="metadata">
                            <source src="{{ $showcase['video_src'] }}" type="video/mp4">
                        </video>
                    @else
                        <div class="mt-5 flex aspect-[16/10] w-full items-center justify-center overflow-hidden rounded-[1.35rem] border border-[color:var(--border-soft)] bg-[radial-gradient(circle_at_top_right,rgba(37,99,235,0.18),transparent_16rem),var(--page-bg-strong)] p-6">
                            <div class="max-w-[20rem] text-center">
                                <p class="text-[1.05rem] font-semibold text-[var(--text-primary)]">{{ $showcase['placeholder'] }}</p>
                                <p class="mt-2 text-[0.92rem] leading-[1.7] text-[var(--text-secondary)]">Replace this placeholder with your screen recording when you are ready.</p>
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="py-8 pb-12">
        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="{{ $panelClass }}">
                <p class="{{ $panelKickerClass }}">What the product emphasizes</p>
                <h2 class="{{ $panelTitleClass }}">A strong balance of UX polish, private context, and extensible AI infrastructure.</h2>
                <ul class="mt-4 grid gap-4 pl-4 text-[var(--text-secondary)]">
                    @foreach ($proofPoints as $point)
                        <li class="leading-[1.8]">{{ $point }}</li>
                    @endforeach
                </ul>
            </article>

            <article class="relative overflow-hidden rounded-[1.7rem] border border-[color:var(--border-soft)] bg-[radial-gradient(circle_at_top_right,rgba(37,99,235,0.2),transparent_15rem),var(--surface-elevated)] p-5 shadow-[var(--shadow-soft)]">
                <p class="{{ $panelKickerClass }}">{{ $cta['kicker'] ?? 'Ready to use it?' }}</p>
                <h2 class="{{ $sectionHeadingClass }}">{{ $cta['title'] ?? 'Create an assistant, ingest your sources, and start chatting from your own knowledge.' }}</h2>
                <p class="{{ $copyClass }}">
                    {{ $cta['description'] ?? 'The platform already supports website crawling, document uploads, vector retrieval, realtime chat, and per-user assistant customization.' }}
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    @auth
                        <a href="{{ route('chat') }}" class="{{ $primaryButtonClass }}">
                            Go to chat
                        </a>
                        <a href="{{ route('filament.admin.pages.assistant-settings') }}" class="{{ $secondaryButtonClass }}">
                            Open assistant settings
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="{{ $primaryButtonClass }}">
                            Get started
                        </a>
                    @endauth
                </div>
            </article>
        </div>
    </section>
@endsection
