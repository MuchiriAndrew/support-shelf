<div class="supportshelf-admin-grid">
    <section class="supportshelf-admin-card">
        <div class="supportshelf-admin-card-head">
            <div>
                <p class="supportshelf-admin-card-kicker">Website ingestion</p>
                <h2 class="supportshelf-admin-card-title">Add website sources</h2>
            </div>
        </div>

        <form wire:submit="createSource" class="space-y-6">
            {{ $this->siteForm }}

            <div class="flex justify-end">
                <x-filament::button type="submit" size="sm">
                    Save website source
                </x-filament::button>
            </div>
        </form>
    </section>

    <section class="supportshelf-admin-card">
        <div class="supportshelf-admin-card-head">
            <div>
                <p class="supportshelf-admin-card-kicker">Document ingestion</p>
                <h2 class="supportshelf-admin-card-title">Import private documents</h2>
            </div>
        </div>

        <form wire:submit="importDocument" class="space-y-6">
            {{ $this->documentForm }}

            <div class="flex justify-end">
                <x-filament::button type="submit" size="sm">
                    Import document
                </x-filament::button>
            </div>
        </form>
    </section>

    <x-filament-actions::modals />
</div>
