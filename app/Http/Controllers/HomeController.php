<?php

namespace App\Http\Controllers;

use App\Models\LandingPageContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $content = LandingPageContent::home()?->content() ?? \App\Support\LandingPageDefaults::content();
        $showcases = collect($content['showcases'] ?? [])
            ->map(function (array $showcase): array {
                $videoPath = $showcase['video_path'] ?? null;

                return [
                    ...$showcase,
                    'video_src' => filled($videoPath) ? Storage::disk('public')->url($videoPath) : null,
                ];
            })
            ->all();

        return view('home', [
            'hero' => $content['hero'] ?? [],
            'heroMetrics' => $content['metrics'] ?? [],
            'pillars' => $content['pillars'] ?? [],
            'workflow' => $content['workflow'] ?? [],
            'showcases' => $showcases,
            'proofPoints' => $content['proof_points'] ?? [],
            'cta' => $content['cta'] ?? [],
        ]);
    }
}
