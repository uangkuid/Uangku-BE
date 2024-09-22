<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

// /**
//  * route "/user"
//  * @method "GET"
//  */
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(UserController::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', 'store');
        Route::post('login', 'login');
        Route::post('logout', 'logout');
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('user', 'index');
    });
});
