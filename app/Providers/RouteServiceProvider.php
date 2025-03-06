<?php


namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * このアプリケーションのルートのホームディレクトリ。
     */
    public const HOME = '/home';

    /**
     * ルートのバインディングやパターンフィルターを定義
     */
    public function boot()
    {
        $this->routes(function () {
            Route::middleware('api') // 👈 `api.php` のルートを適用
                ->prefix('api')  // 👈 `api/` プレフィックスを適用
                ->group(base_path('routes/api.php')); // 👈 `api.php` をロード

            Route::middleware('web')
                ->group(base_path('routes/web.php')); // 👈 `web.php` もロード
        });
    }
}

