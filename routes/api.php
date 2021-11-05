<?php
namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['namespace', 'api'], function () {
    Route::post('/user-register', [UserController::class , 'register']);
    Route::post('/otp-confirm', [UserController::class, 'otp_confirm']);
    Route::post('/user-login', [UserController::class, 'user_login']);
    Route::post('/profile-update/{id}', [UserController::class, 'update_profile']);
});
