<?php

use App\Http\Controllers\ProxyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('main');
});

Route::post('/check-proxies', [ProxyController::class, 'checkProxies']);
Route::post('/check-proxies', [ProxyController::class, 'checkProxies']);
Route::get('/check-proxies/progress', [ProxyController::class, 'getProgress']);
Route::get('/done-jobs', [ProxyController::class, 'getDoneJobs']);
