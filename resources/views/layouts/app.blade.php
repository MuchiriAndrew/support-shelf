@php
    $brandName = config('support-assistant.brand.name');
    $brandTagline = config('support-assistant.brand.tagline');
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
    $navigation = [
        ['label' => 'Chat', 'route' => 'chat'],
        ['label' => 'Overview', 'route' => 'home'],
        ['label' => 'Ingestion', 'route' => 'filament.admin.pages.knowledge-ingestion'],
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
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <body @class([
        'site-body',
        'site-body-chat' => $isChatPage,
    ])>
        <div x-data="siteChrome(@js(['pageKind' => $pageKind]))" x-init="init()" @class([
            'site-shell',
            'site-shell-chat' => $isChatPage,
        ])>
            <div class="site-shell-glow"></div>

            <header class="shell-navbar">
                <div @class([
                    'shell-navbar-inner',
                    'mx-auto w-full',
                    $navContentWidth => $navContentWidth !== 'max-w-none',
                ])>
                    <a href="{{ route('chat') }}" class="shell-brand">
                        <!-- <span class="brand-mark text-lg font-semibold">S</span> -->
                         <div class="hidden md:flex">
                            <x-app-emblem />
                        </div>
                        <div>
                            <!-- <p class="shell-brand-kicker">Support assistant</p> -->
                            <p class="shell-brand-title">Support Shelf</p>
                        </div>
                    </a>

                    <nav class="hidden items-center gap-2 md:flex">
                        @foreach ($navigation as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                @class([
                                    'nav-pill rounded-full px-4 py-2 text-sm font-medium',
                                    'nav-pill-active' => request()->routeIs($item['route']),
                                ])
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="shell-navbar-actions">
                        <!-- <div class="hidden md:flex">
                            <x-app-emblem />
                        </div> -->

                        <button type="button" class="theme-toggle" @click="toggleTheme" :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'">
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
                            class="shell-menu-button md:hidden"
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
                    class="shell-offcanvas-overlay md:hidden"
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
                    class="shell-offcanvas md:hidden"
                >
                    <div class="shell-offcanvas-header">
                        <div>
                            <p class="shell-brand-kicker">Navigate</p>
                            <p class="shell-brand-title">{{ $brandName }}</p>
                        </div>
                        <button type="button" class="shell-close-button" @click="closeMobileNav" aria-label="Close menu">
                            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 6L18 18"></path>
                                <path d="M18 6L6 18"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="mt-8 space-y-3">
                        @foreach ($navigation as $item)
                            <a href="{{ route($item['route']) }}" class="shell-offcanvas-link" @click="closeMobileNav">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </aside>
            @endunless

            <main @class([
                'relative flex-1',
                'site-main-chat' => $isChatPage,
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
                            <p>
                                Built by
                                <a
                                    href="https://portfolio.mkbuilds.live"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="footer-signature-link"
                                >
                                    Andrew Muchiri
                                </a>
                            </p>
                        </div>
                    </div>
                </footer>
            @endunless
        </div>
    </body>
</html>
