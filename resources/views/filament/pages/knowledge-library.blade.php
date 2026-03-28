<x-filament-panels::page>
    <div class="supportshelf-admin-shell">
        <div class="supportshelf-admin-intro">
            <div>
                <p class="supportshelf-admin-kicker">Knowledge library</p>
                <h1 class="supportshelf-admin-heading">Review everything the assistant can search and cite</h1>
                <p class="supportshelf-admin-copy">
                    Browse your crawled websites and uploaded documents separately so it is clear what came from a site and what was added manually.
                </p>
            </div>

            <div class="supportshelf-admin-highlight">
                <p class="supportshelf-admin-highlight-label">Inside the library</p>
                <ul class="supportshelf-admin-list">
                    <li>Sites with their stored pages and crawl state</li>
                    <li>Uploaded files such as PDFs, text, markdown, and HTML</li>
                    <li>Vector index status for retrieval readiness in your workspace</li>
                </ul>
            </div>
        </div>

        <div x-data="{ libraryView: 'sites' }">
            <div class="supportshelf-admin-card-head">
                <div>
                    <p class="supportshelf-admin-card-kicker">Stored knowledge</p>
                    <h2 class="supportshelf-admin-card-title">Inspect sites and uploaded files from one place</h2>
                </div>

                <div class="supportshelf-admin-segmented">
                    <button
                        type="button"
                        class="supportshelf-admin-segment"
                        :class="{ 'supportshelf-admin-segment-active': libraryView === 'sites' }"
                        @click="libraryView = 'sites'"
                    >
                        Sites
                    </button>
                    <button
                        type="button"
                        class="supportshelf-admin-segment"
                        :class="{ 'supportshelf-admin-segment-active': libraryView === 'documents' }"
                        @click="libraryView = 'documents'"
                    >
                        Documents
                    </button>
                </div>
            </div>

            <div x-show="libraryView === 'sites'">
                <livewire:admin.sources-library-table />
            </div>

            <div x-show="libraryView === 'documents'" x-cloak>
                <livewire:admin.knowledge-library-table />
            </div>
        </div>
    </div>
</x-filament-panels::page>
