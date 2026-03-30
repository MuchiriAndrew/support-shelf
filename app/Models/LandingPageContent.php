<?php

namespace App\Models;

use App\Support\LandingPageDefaults;
use Illuminate\Database\Eloquent\Model;

class LandingPageContent extends Model
{
    protected $fillable = [
        'slug',
        'hero',
        'metrics',
        'pillars',
        'workflow',
        'showcases',
        'proof_points',
        'cta',
    ];

    protected function casts(): array
    {
        return [
            'hero' => 'array',
            'metrics' => 'array',
            'pillars' => 'array',
            'workflow' => 'array',
            'showcases' => 'array',
            'proof_points' => 'array',
            'cta' => 'array',
        ];
    }

    public static function home(): ?self
    {
        return static::query()->where('slug', 'home')->first();
    }

    /**
     * @return array{
     *     hero: array<string, mixed>,
     *     metrics: array<int, array<string, mixed>>,
     *     pillars: array<int, array<string, mixed>>,
     *     workflow: array<int, array<string, mixed>>,
     *     showcases: array<int, array<string, mixed>>,
     *     proof_points: array<int, string>,
     *     cta: array<string, mixed>
     * }
     */
    public function content(): array
    {
        $defaults = LandingPageDefaults::content();

        return [
            'hero' => is_array($this->hero) ? array_replace($defaults['hero'], $this->hero) : $defaults['hero'],
            'metrics' => is_array($this->metrics) && $this->metrics !== [] ? $this->metrics : $defaults['metrics'],
            'pillars' => is_array($this->pillars) && $this->pillars !== [] ? $this->pillars : $defaults['pillars'],
            'workflow' => is_array($this->workflow) && $this->workflow !== [] ? $this->workflow : $defaults['workflow'],
            'showcases' => is_array($this->showcases) && $this->showcases !== [] ? $this->showcases : $defaults['showcases'],
            'proof_points' => is_array($this->proof_points) && $this->proof_points !== [] ? $this->proof_points : $defaults['proof_points'],
            'cta' => is_array($this->cta) ? array_replace($defaults['cta'], $this->cta) : $defaults['cta'],
        ];
    }
}
