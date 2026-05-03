<?php

declare(strict_types=1);

namespace Mosab\QafyaDetector\Laravel;

use Illuminate\Support\ServiceProvider;
use Mosab\QafyaDetector\Contracts\PoemQafyaDetectorContract;
use Mosab\QafyaDetector\Contracts\QafyaDetectorContract;
use Mosab\QafyaDetector\Contracts\WordQafyaDetectorContract;
use Mosab\QafyaDetector\PoemQafyaDetector;
use Mosab\QafyaDetector\QafyaDetector;
use Mosab\QafyaDetector\WordQafyaDetector;

final class MosabQafyaDetectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/mosab-qafya-detector.php', 'mosab-qafya-detector');
        $this->app->singleton(WordQafyaDetectorContract::class, WordQafyaDetector::class);
        $this->app->singleton(PoemQafyaDetectorContract::class, PoemQafyaDetector::class);
        $this->app->singleton(QafyaDetectorContract::class, QafyaDetector::class);
        $this->app->singleton(QafyaDetector::class, QafyaDetector::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/mosab-qafya-detector.php' => function_exists('config_path') ? \config_path('mosab-qafya-detector.php') : 'config/mosab-qafya-detector.php',
            ], 'mosab-qafya-detector-config');
        }
    }
}
