<x-filament-panels::page>
    <div class="supportshelf-admin-shell">
        <div class="supportshelf-admin-intro">
            <div>
                <p class="supportshelf-admin-kicker">CMS</p>
                <h1 class="supportshelf-admin-heading">Manage the public landing page from the super admin panel</h1>
                <p class="supportshelf-admin-copy">
                    Update homepage messaging, workflow sections, proof points, and showcase videos without touching Blade files.
                </p>
            </div>

            <div class="supportshelf-admin-highlight">
                <p class="supportshelf-admin-highlight-label">What is managed here</p>
                <ul class="supportshelf-admin-list">
                    <li>Hero copy and supporting metrics</li>
                    <li>Value pillars, workflow storytelling, and proof points</li>
                    <li>Video-driven showcase sections for ingestion and live chat demos</li>
                </ul>
            </div>
        </div>

        <form wire:submit="save" class="supportshelf-admin-card">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" size="md">
                    Save landing page
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
