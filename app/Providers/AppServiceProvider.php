<?php declare(strict_types=1);

namespace App\Providers;

use App\Contracts\QuestionGeneratorContract;
use App\Services\QuestionGeneratorService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QuestionGeneratorContract::class, function (): QuestionGeneratorService {
            return new QuestionGeneratorService(
                http: $this->app->make(HttpFactory::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
