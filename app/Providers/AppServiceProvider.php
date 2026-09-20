<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Number;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('rupiah', function($nominal) {
            return "<?php echo 'Rp ' . number_format($nominal, 0, ',', '.'); ?>";
        });
    }
}
