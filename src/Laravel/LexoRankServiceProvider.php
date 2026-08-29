<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Laravel;

use Illuminate\Support\ServiceProvider;
use MuradyanVano\LexoRank\LexoRankService;
use MuradyanVano\LexoRank\Math\NumeralSystem36;
use MuradyanVano\LexoRank\Rebalancer;

final class LexoRankServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/lexorank.php', 'lexorank');

        $this->app->singleton(LexoRankService::class, static function ($app): LexoRankService {
            /** @var array{max_count?: int, max_rank_length?: int} $config */
            $config = $app['config']->get('lexorank', []);

            return new LexoRankService(
                (int) ($config['max_count'] ?? LexoRankService::DEFAULT_MAX_COUNT),
                (int) ($config['max_rank_length'] ?? NumeralSystem36::DEFAULT_MAX_RANK_LENGTH),
            );
        });

        $this->app->singleton(Rebalancer::class, static function ($app): Rebalancer {
            return new Rebalancer($app->make(LexoRankService::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/lexorank.php' => config_path('lexorank.php'),
            ], 'lexorank-config');
        }
    }
}
