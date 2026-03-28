<x-filament-panels::page>
    <div class="supportshelf-admin-shell">
        <div class="supportshelf-admin-intro">
            <div>
                <p class="supportshelf-admin-kicker">Knowledge operations</p>
                <h1 class="supportshelf-admin-heading">Bring your private context into the assistant</h1>
                <p class="supportshelf-admin-copy">
                    Add websites and upload documents so your assistant can answer from your own private context.
                </p>
            </div>

            <div class="supportshelf-admin-highlight">
                <p class="supportshelf-admin-highlight-label">What this page manages</p>
                <ul class="supportshelf-admin-list">
                    <li>Website source registration with automatic crawling</li>
                    <li>Document imports for PDFs, text, markdown, and HTML</li>
                    <li>A simpler starting point for building your private knowledge base</li>
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
