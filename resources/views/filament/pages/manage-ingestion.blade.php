<x-filament-panels::page>
    <div class="supportshelf-admin-shell">
        <div class="supportshelf-admin-intro">
            <div>
                <p class="supportshelf-admin-kicker">Knowledge operations</p>
                <h1 class="supportshelf-admin-heading">Bring support content into the assistant</h1>
                <p class="supportshelf-admin-copy">
                    Add support sites, upload manuals or policy files, and kick off training so the assistant stays ready for grounded answers.
                </p>
            </div>

            <div class="supportshelf-admin-highlight">
                <p class="supportshelf-admin-highlight-label">What this page manages</p>
                <ul class="supportshelf-admin-list">
                    <li>Support site registration and crawl triggers</li>
                    <li>Document imports for manuals, FAQs, and policy files</li>
                    <li>Training actions for the searchable knowledge base</li>
                </ul>

                <div class="supportshelf-admin-inline-actions">
                    <x-filament::button
                        tag="a"
                        :href="route('filament.admin.pages.knowledge-library')"
                        size="sm"
                        color="gray"
                    >
                        Open knowledge library
                    </x-filament::button>
                </div>
            </div>
        </div>

        <livewire:admin.ingestion-workspace />
    </div>
</x-filament-panels::page>
