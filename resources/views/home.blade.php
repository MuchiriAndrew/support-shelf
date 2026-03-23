@extends('layouts.app', ['pageTitle' => 'Overview', 'contentWidth' => 'max-w-[96rem]'])

@section('content')
    <section class="relative overflow-hidden border-b border-[color:var(--border-soft)] pb-18 pt-14 sm:pb-24 sm:pt-20">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-[rgba(255,255,255,0.16)] to-transparent"></div>

        <div class="grid gap-14 xl:grid-cols-[1.08fr_0.92fr] xl:items-end">
            <div class="max-w-5xl">
                <p class="text-sm font-semibold uppercase tracking-[0.34em] text-[var(--text-muted)]">Premium support e-commerce assistant</p>
                <h1 class="mt-6 max-w-5xl text-5xl font-semibold tracking-[-0.055em] text-[var(--text-primary)] sm:text-6xl xl:text-7xl">
                    Turn scattered support content into one calm, trustworthy conversation.
                </h1>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-[var(--text-secondary)] sm:text-xl">
                    SupportShelf AI brings manuals, policies, and help-center guidance together so customers can ask naturally and get answers that feel direct, useful, and grounded in your own knowledge base.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('chat') }}" class="chat-primary-button">
                        Open the assistant
                    </a>
                    <a href="{{ route('filament.admin.pages.knowledge-ingestion') }}" class="chat-secondary-button px-6 py-3 text-sm">
                        Manage support content
                    </a>
                </div>
            </div>

            <div class="space-y-6 border-t border-[color:var(--border-soft)] pt-8 xl:border-l xl:border-t-0 xl:pl-12 xl:pt-0">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[var(--text-muted)]">Built for support teams</p>
                @foreach ($foundation as $item)
                    <p class="max-w-xl text-base leading-8 text-[var(--text-secondary)]">{{ $item }}</p>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-b border-[color:var(--border-soft)] py-12 sm:py-16">
        <div class="grid gap-10 lg:grid-cols-3">
            @foreach ($pillars as $pillar)
                <article class="border-b border-[color:var(--border-soft)] pb-8 lg:border-b-0 lg:border-r lg:pb-0 lg:pr-8 last:border-r-0 last:pr-0">
                    <p class="text-sm font-semibold uppercase tracking-[0.26em] text-[var(--text-muted)]">What it does</p>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-[var(--text-primary)]">{{ $pillar['title'] }}</h2>
                    <p class="mt-4 text-base leading-7 text-[var(--text-secondary)]">{{ $pillar['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="py-12 sm:py-16">
        <div class="grid gap-12 lg:grid-cols-[0.92fr_1.08fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[var(--text-muted)]">How it comes together</p>
                <h2 class="mt-4 max-w-xl text-3xl font-semibold tracking-tight text-[var(--text-primary)] sm:text-4xl">
                    From disconnected support material to a single answer experience customers can rely on.
                </h2>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                @foreach ($journey as $step)
                    <div class="border-t border-[color:var(--border-soft)] pt-4">
                        <p class="text-base leading-7 text-[var(--text-secondary)]">{{ $step }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
