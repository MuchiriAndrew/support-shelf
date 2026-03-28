@php
    $brandName = config('assistant.brand.name');
    $brandTagline = config('assistant.brand.tagline');
    $metaTitle = isset($pageTitle) ? "{$pageTitle} | {$brandName}" : $brandName;
    $pageKind = $pageKind ?? 'default';
    $isChatPage = $pageKind === 'chat';
    $fullWidth = $fullWidth ?? $isChatPage;
    $hideFooter = $hideFooter ?? false;
    $contentWidth = $contentWidth ?? 'max-w-7xl';
    $navContentWidth = $navContentWidth ?? ($fullWidth ? 'max-w-none' : $contentWidth);
    $mainContainerClass = $fullWidth
        ? 'h-full w-full'
        : "mx-auto w-full {$contentWidth} px-4 sm:px-6 lg:px-8";
    $footerContainerClass = $fullWidth
        ? 'w-full px-4 sm:px-6 lg:px-8'
        : "mx-auto w-full {$contentWidth} px-4 sm:px-6 lg:px-8";
    $bodyClass = $isChatPage
        ? 'h-[var(--app-height)] min-h-[var(--app-height)] overflow-hidden overscroll-none bg-[var(--page-bg)] text-[var(--text-primary)]'
        : 'min-h-[var(--app-height)] bg-[var(--page-bg)] text-[var(--text-primary)]';
    $shellClass = $isChatPage
        ? 'relative flex h-[var(--app-height)] min-h-[var(--app-height)] flex-col overflow-hidden bg-[var(--page-bg)] text-[var(--text-primary)]'
        : 'relative min-h-screen overflow-x-clip overflow-y-visible bg-[var(--page-bg)] text-[var(--text-primary)]';
    $navButtonClass = 'inline-flex h-11 w-11 items-center justify-center rounded-full border border-[color:var(--button-secondary-border)] bg-[var(--button-secondary-bg)] text-[var(--text-primary)] transition hover:bg-[var(--page-bg-strong)]';
    $navLinkBase = 'rounded-full border px-4 py-2 text-sm font-medium transition';
    $navLinkActive = 'border-[color:var(--border-soft)] bg-[var(--button-secondary-bg)] text-[var(--text-primary)]';
    $navLinkInactive = 'border-transparent text-[var(--text-secondary)] hover:border-[color:var(--button-secondary-border)] hover:bg-[var(--button-secondary-bg)] hover:text-[var(--text-primary)]';
    $offcanvasOverlayClass = 'fixed inset-0 z-40 bg-black/45 backdrop-blur-sm';
    $offcanvasClass = 'fixed inset-y-0 right-0 z-50 w-[min(100%,21rem)] overflow-y-auto border-l border-[color:var(--border-soft)] bg-[var(--drawer-bg)] px-5 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-[calc(1rem+env(safe-area-inset-top))] shadow-[var(--shadow-soft)]';
    $offcanvasLinkClass = 'block rounded-2xl border border-transparent px-4 py-3 text-sm font-medium text-[var(--text-primary)] transition hover:border-[color:var(--button-secondary-border)] hover:bg-[var(--button-secondary-bg)]';
    $navigation = auth()->check()
        ? [
            ['label' => 'Overview', 'route' => 'home'],
            ['label' => 'Chat', 'route' => 'chat'],
            ['label' => 'Knowledge', 'route' => 'filament.admin.pages.knowledge-ingestion'],
            ['label' => 'My Assistant', 'route' => 'filament.admin.pages.assistant-settings'],
        ]
        : [
            ['label' => 'Overview', 'route' => 'home'],
            ['label' => 'Login', 'route' => 'login'],
            ['label' => 'Register', 'route' => 'register'],
        ];
    $reverbApp = config('reverb.apps.apps.0');
    $reverbOptions = $reverbApp['options'] ?? [];
    $reverbRuntimeConfig = [
        'appKey' => $reverbApp['key'] ?? null,
        'host' => $reverbOptions['public_host'] ?? $reverbOptions['host'] ?? request()->getHost(),
        'port' => $reverbOptions['public_port'] ?? $reverbOptions['port'] ?? 443,
        'scheme' => $reverbOptions['public_scheme'] ?? $reverbOptions['scheme'] ?? 'https',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-realtime="{{ ($realtime ?? false) ? 'true' : 'false' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="description" content="{{ $brandTagline }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $metaTitle }}</title>

        <script>
            window.supportShelfConfig = @json([
                'reverb' => $reverbRuntimeConfig,
            ]);
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="m-0 overflow-x-hidden antialiased {{ $bodyClass }}">
        <div x-data="siteChrome(@js(['pageKind' => $pageKind]))" x-init="init()" class="{{ $shellClass }}">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -left-24 top-[-7rem] h-[28rem] w-[28rem] rounded-full bg-blue-500/15 blur-[130px]"></div>
                <div class="absolute right-[-5rem] top-[-4rem] h-[22rem] w-[22rem] rounded-full bg-violet-500/10 blur-[120px]"></div>
            </div>

            <header class="sticky top-0 z-40 min-h-[var(--nav-height)] border-b border-[color:var(--border-soft)] bg-[var(--nav-bg)] backdrop-blur-[18px]">
                <div @class([
                    'mx-auto flex w-full items-center justify-between gap-4 px-3 py-[0.85rem] sm:px-6 lg:px-8',
                    $navContentWidth => $navContentWidth !== 'max-w-none',
                ])>
                    <a href="{{ auth()->check() ? route('chat') : route('home') }}" class="flex items-center gap-3">
                        <div class="hidden md:flex">
                            <x-app-emblem />
                        </div>
                        <div>
                            <p class="text-base font-semibold text-[var(--text-primary)]">Support Shelf</p>
                        </div>
                    </a>

                    <nav class="hidden items-center gap-2 md:flex">
                        @foreach ($navigation as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                @class([
                                    $navLinkBase,
                                    $navLinkActive => request()->routeIs($item['route']),
                                    $navLinkInactive => ! request()->routeIs($item['route']),
                                ])
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="flex items-center gap-3">
                        @auth
                            <span class="hidden text-sm font-medium text-[var(--text-secondary)] lg:inline-flex">
                                {{ auth()->user()->name }}
                            </span>
                        @endauth

                        <button type="button" class="{{ $navButtonClass }}" @click="toggleTheme" :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'">
                            <svg x-show="theme === 'dark'" x-cloak viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="4.2"></circle>
                                <path d="M12 2.5V5"></path>
                                <path d="M12 19V21.5"></path>
                                <path d="M4.93 4.93L6.7 6.7"></path>
                                <path d="M17.3 17.3L19.07 19.07"></path>
                                <path d="M2.5 12H5"></path>
                                <path d="M19 12H21.5"></path>
                                <path d="M4.93 19.07L6.7 17.3"></path>
                                <path d="M17.3 6.7L19.07 4.93"></path>
                            </svg>
                            <svg x-show="theme === 'light'" x-cloak viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"></path>
                            </svg>
                        </button>

                        <button
                            type="button"
                            class="{{ $navButtonClass }} md:hidden"
                            @click="openMobileMenu"
                            aria-label="Open navigation menu"
                        >
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 7H20"></path>
                                <path d="M4 12H20"></path>
                                <path d="M4 17H14"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            @unless ($isChatPage)
                <div
                    x-cloak
                    x-show="mobileNavOpen"
                    x-transition.opacity
                    class="{{ $offcanvasOverlayClass }} md:hidden"
                    @click="closeMobileNav"
                ></div>

                <aside
                    x-cloak
                    x-show="mobileNavOpen"
                    x-transition:enter="transition duration-300 ease-out"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition duration-200 ease-in"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="{{ $offcanvasClass }} md:hidden"
                >
                    <div class="sticky top-0 flex items-center justify-between gap-4 border-b border-[color:var(--border-soft)] bg-[var(--drawer-bg)] pb-4">
                        <div>
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[var(--text-muted)]">Navigate</p>
                            <p class="mt-1 text-base font-semibold text-[var(--text-primary)]">{{ $brandName }}</p>
                        </div>
                        <button type="button" class="{{ $navButtonClass }}" @click="closeMobileNav" aria-label="Close menu">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 6L18 18"></path>
                                <path d="M18 6L6 18"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="mt-8 space-y-3">
                        @foreach ($navigation as $item)
                            <a href="{{ route($item['route']) }}" class="{{ $offcanvasLinkClass }}" @click="closeMobileNav">
                                {{ $item['label'] }}
                            </a>
                        @endforeach

                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="{{ $offcanvasLinkClass }} w-full text-left">
                                    Logout
                                </button>
                            </form>
                        @endauth
                    </div>
                </aside>
            @endunless

            <main @class([
                'relative flex-1',
                'min-h-0 overflow-hidden' => $isChatPage,
            ])>
                <div class="{{ $mainContainerClass }}">
                    @yield('content')
                </div>
            </main>

            @unless ($hideFooter)
                <footer class="relative z-10 border-t border-[color:var(--border-soft)] py-6">
                    <div class="{{ $footerContainerClass }}">
                        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-[var(--text-muted)]">
                            <p>{{ $brandTagline }}</p>
                            <div class="flex flex-wrap items-center gap-4">
                                @auth
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]">
                                            Logout
                                        </button>
                                    </form>
                                @endauth
                                <p>
                                    Built by
                                    <a
                                        href="https://portfolio.mkbuilds.live"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="text-sm font-medium text-[var(--text-secondary)] transition hover:text-[var(--text-primary)]"
                                    >
                                        Andrew Muchiri
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </footer>
            @endunless
        </div>
    </body>
</html>
