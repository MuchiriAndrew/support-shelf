<x-filament-panels::page>
    <div class="supportshelf-admin-shell">
        <div class="supportshelf-admin-intro">
            <div>
                <p class="supportshelf-admin-kicker">Knowledge library</p>
                <h1 class="supportshelf-admin-heading">Review everything the assistant can search and cite</h1>
                <p class="supportshelf-admin-copy">
                    Browse imported documents and registered support sites, filter the library, and confirm what has already been indexed into Weaviate.
                </p>
            </div>

            <div class="supportshelf-admin-highlight">
                <p class="supportshelf-admin-highlight-label">Inside the library</p>
                <ul class="supportshelf-admin-list">
                    <li>Documents imported from manuals, guides, and policies</li>
                    <li>Support sites registered for crawling and recrawling</li>
                    <li>Vector index status for retrieval readiness</li>
                </ul>
            </div>
        </div>

        <section class="supportshelf-admin-card" x-data="{ libraryView: 'documents' }">
            <div class="supportshelf-admin-card-head">
                <div>
                    <p class="supportshelf-admin-card-kicker">Stored knowledge</p>
                    <h2 class="supportshelf-admin-card-title">Inspect documents and source sites from one place</h2>
                </div>

                <div class="supportshelf-admin-segmented">
                    <button
                        type="button"
                        class="supportshelf-admin-segment"
                        :class="{ 'supportshelf-admin-segment-active': libraryView === 'documents' }"
                        @click="libraryView = 'documents'"
                    >
                        Documents
                    </button>
                    <button
                        type="button"
                        class="supportshelf-admin-segment"
                        :class="{ 'supportshelf-admin-segment-active': libraryView === 'sites' }"
                        @click="libraryView = 'sites'"
                    >
                        Sites
                    </button>
                </div>
            </div>

            <div x-show="libraryView === 'documents'">
                <livewire:admin.knowledge-library-table />
            </div>

            <div x-show="libraryView === 'sites'" x-cloak>
                <livewire:admin.sources-library-table />
            </div>
        </section>
    </div>
</x-filament-panels::page>
