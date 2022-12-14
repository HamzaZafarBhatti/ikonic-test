<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('get-suggestions', [HomeController::class, 'getSuggestions']);
Route::get('get-connections', [HomeController::class, 'getConnections']);
Route::get('get-request', [HomeController::class, 'getRequest']);

Route::post('send-connection-request', [HomeController::class, 'sendConnectionRequest']);
Route::post('delete-connection-request', [HomeController::class, 'deleteConnectionRequest']);
Route::post('accept-connection-request', [HomeController::class, 'acceptConnectionRequest']);
Route::post('remove-connection-request', [HomeController::class, 'removeConnectionRequest']);