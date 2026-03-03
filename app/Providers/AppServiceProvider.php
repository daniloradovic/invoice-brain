<?php

namespace App\Providers;

use App\Services\MoneyService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::directive('money', fn (string $expression): string => "<?php echo \App\Services\MoneyService::format({$expression}); ?>");
    }
}
