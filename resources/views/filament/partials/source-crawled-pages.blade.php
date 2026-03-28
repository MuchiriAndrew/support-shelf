<div class="space-y-4">
    @if ($documents->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-600 dark:border-white/10 dark:text-gray-300">
            No crawled pages have been stored for this site yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($documents as $document)
                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $document->title }}
                        </p>

                        @if ($document->canonical_url)
                            <a
                                href="{{ $document->canonical_url }}"
                                target="_blank"
                                rel="noreferrer"
                                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                            >
                                {{ $document->canonical_url }}
                            </a>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No canonical URL stored for this page.</p>
                        @endif

                        <p class="text-xs uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                            Updated {{ optional($document->updated_at)?->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
