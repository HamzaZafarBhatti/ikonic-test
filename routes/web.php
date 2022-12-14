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

Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('get-suggested-connections', [HomeController::class, 'getSuggestedConnections']);
Route::get('get-sent-request', [HomeController::class, 'getSentRequest']);

Route::post('send-connection-request', [HomeController::class, 'sendConnectionRequest']);