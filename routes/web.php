<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\AvailabilityController;


Route::get('/', function () {
    return view('booking');
})->name('home');

// Availability api
Route::get('/api/availability', [AvailabilityController::class, 'index'])->name('api.availability');

Route::get('/health', function () {
    $checks = [
        'db' => false,
        'cache' => false,
        'storage' => false,
        'app_key' => false,
    ];

    try {
        DB::connection()->getPdo();
        $checks['db'] = true;
    } catch (\Throwable $e) {
        // leave as false
    }

    try {
        Cache::put('health_check', 'ok', 10);
        $checks['cache'] = Cache::get('health_check') === 'ok';
    } catch (\Throwable $e) {
        // leave as false
    }

    $checks['storage'] = is_writable(storage_path());
    $checks['app_key'] = ! empty(config('app.key'));

    $overallOk = ! in_array(false, $checks, true);

    return response()->json([
        'status' => $overallOk ? 'ok' : 'error',
    ], $overallOk ? 200 : 500);
})->name('health');
