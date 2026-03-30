<?php

namespace App\Services\Admin;

use App\Models\AssistantConversation;
use App\Models\Document;
use App\Models\Source;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    /**
     * @return array{
     *     users:int,
     *     customers:int,
     *     super_admins:int,
     *     sources:int,
     *     documents:int,
     *     conversations:int
     * }
     */
    public function totals(): array
    {
        $users = User::query()->count();

        return [
            'users' => $users,
            'customers' => User::query()->role(User::ROLE_CUSTOMER)->count(),
            'super_admins' => User::query()->role(User::ROLE_SUPER_ADMIN)->count(),
            'sources' => Source::query()->count(),
            'documents' => Document::query()->count(),
            'conversations' => AssistantConversation::query()->count(),
        ];
    }

    /**
     * @return array{
     *     labels:list<string>,
     *     users:list<int>,
     *     documents:list<int>,
     *     conversations:list<int>
     * }
     */
    public function dailySeries(int $days = 7): array
    {
        $days = max(1, $days);
        $window = collect(range($days - 1, 0))
            ->map(fn (int $offset): Carbon => now()->copy()->subDays($offset)->startOfDay())
            ->values();

        return [
            'labels' => $window->map(fn (Carbon $day): string => $day->format('M j'))->all(),
            'users' => $this->mapSeries($window, User::query(), 'created_at'),
            'documents' => $this->mapSeries($window, Document::query(), 'created_at'),
            'conversations' => $this->mapSeries($window, AssistantConversation::query(), 'created_at'),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  Collection<int, CarbonInterface>  $window
     * @return list<int>
     */
    protected function mapSeries(Collection $window, $query, string $column): array
    {
        $start = $window->first()?->copy()->startOfDay();
        $end = $window->last()?->copy()->endOfDay();

        if (! $start || ! $end) {
            return [];
        }

        /** @var array<string, int> $counts */
        $counts = $query
            ->whereBetween($column, [$start, $end])
            ->selectRaw('DATE(' . DB::getQueryGrammar()->wrap($column) . ') as summary_date')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('summary_date')
            ->pluck('aggregate', 'summary_date')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        return $window
            ->map(fn (CarbonInterface $day): int => $counts[$day->toDateString()] ?? 0)
            ->all();
    }
}
