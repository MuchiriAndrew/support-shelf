<div class="supportshelf-admin-grid">
    <section class="supportshelf-admin-card">
        <div class="supportshelf-admin-card-head">
            <div>
                <p class="supportshelf-admin-card-kicker">Site ingestion</p>
                <h2 class="supportshelf-admin-card-title">Add and crawl support websites</h2>
            </div>
        </div>

        <form wire:submit="createSource" class="space-y-6">
            {{ $this->siteForm }}

            <div class="flex justify-end">
                <x-filament::button type="submit" size="sm">
                    Save support site
                </x-filament::button>
            </div>
        </form>
    </section>

    <section class="supportshelf-admin-card">
        <div class="supportshelf-admin-card-head">
            <div>
                <p class="supportshelf-admin-card-kicker">Document ingestion</p>
                <h2 class="supportshelf-admin-card-title">Import manuals, policies, and guides</h2>
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

    <section class="supportshelf-admin-card supportshelf-admin-card-wide">
        <div class="supportshelf-admin-card-head">
            <div>
                <p class="supportshelf-admin-card-kicker">Training controls</p>
                <h2 class="supportshelf-admin-card-title">Run the ingestion and vector workflows</h2>
            </div>
        </div>

        <div class="supportshelf-admin-actions">
            <button type="button" class="supportshelf-admin-action-button" wire:click="crawlAllEnabledSites">
                <span class="supportshelf-admin-action-label">Queue crawl for active sites</span>
                <span class="supportshelf-admin-action-meta">{{ number_format($workspaceStats['sites']) }} registered</span>
            </button>

            <button type="button" class="supportshelf-admin-action-button" wire:click="syncPendingVectors">
                <span class="supportshelf-admin-action-label">Train pending vectors</span>
                <span class="supportshelf-admin-action-meta">
                    @if ($workspaceStats['pendingVectors'] > 0)
                        {{ number_format($workspaceStats['pendingVectors']) }} document(s) waiting
                    @elseif ($workspaceStats['vectorReady'])
                        Knowledge base is synced
                    @else
                        Configure OpenAI + Weaviate first
                    @endif
                </span>
            </button>
        </div>

        <div class="supportshelf-admin-pill-grid">
            <div class="supportshelf-admin-pill">
                <span class="supportshelf-admin-pill-label">Support sites</span>
                <span class="supportshelf-admin-pill-value">{{ number_format($workspaceStats['sites']) }}</span>
            </div>
            <div class="supportshelf-admin-pill">
                <span class="supportshelf-admin-pill-label">Documents</span>
                <span class="supportshelf-admin-pill-value">{{ number_format($workspaceStats['documents']) }}</span>
            </div>
            <div class="supportshelf-admin-pill">
                <span class="supportshelf-admin-pill-label">Pending vector sync</span>
                <span class="supportshelf-admin-pill-value">{{ number_format($workspaceStats['pendingVectors']) }}</span>
            </div>
            <div class="supportshelf-admin-pill">
                <span class="supportshelf-admin-pill-label">Training access</span>
                <span class="supportshelf-admin-pill-value">{{ $workspaceStats['vectorReady'] ? 'Ready' : 'Needs config' }}</span>
            </div>
        </div>
    </section>

    <x-filament-actions::modals />
</div>
