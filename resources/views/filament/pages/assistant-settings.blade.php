<x-filament-panels::page>
    <div class="supportshelf-admin-shell">
        <div class="supportshelf-admin-intro">
            <div>
                <p class="supportshelf-admin-kicker">Assistant identity</p>
                <h1 class="supportshelf-admin-heading">Shape how your assistant presents itself</h1>
                <p class="supportshelf-admin-copy">
                    Set the name and behavioral instructions for your assistant. These settings personalize the experience,
                    while retrieval still keeps every answer grounded in your own private context.
                </p>
            </div>

            <div class="supportshelf-admin-highlight">
                <p class="supportshelf-admin-highlight-label">What you can control here</p>
                <ul class="supportshelf-admin-list">
                    <li>The assistant name shown in chat and across your workspace</li>
                    <li>Optional instructions that steer tone, style, and response preferences</li>
                    <li>A private configuration that applies only to your own account</li>
                </ul>
            </div>
        </div>

        <form wire:submit="save" class="supportshelf-admin-card">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" size="md">
                    Save assistant settings
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
