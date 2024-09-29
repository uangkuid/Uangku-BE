<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Resources\BaseResponse;
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

Route::fallback(function () {
    return response()->json(new BaseResponse(404, "Data not found", null), 404);
});

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

Route::controller(CategoryController::class)->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::get('categories', 'index');
        Route::get('categories/{transactionType}', 'show');
    });
});
