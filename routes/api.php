<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FamiliesController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\TransactionTypeController;
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
        Route::post('refresh-token', 'refreshToken');
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('user', 'index');
    });
});

Route::controller(CategoryController::class)->group(function () {
    Route::get('categories', 'getCategories');
    Route::middleware('auth:api')->group(function () {
        Route::post('categories', 'store');
        Route::put('categories/{id}', 'update');
        Route::delete('categories/{id}', 'destroy');
    });
});

Route::controller(SubCategoryController::class)->middleware('auth:api')->group(function () {
    Route::get('categories/{id}', 'getSubCategories');
    Route::post('categories/{id}', 'store');
    Route::put('categories/{categoryId}/{id}', 'update');
    Route::delete('categories/{categoryId}/{id}', 'destroy');
});

Route::controller(TransactionTypeController::class)->group(function () {
    Route::get('transaction-type', 'index');
    Route::middleware('auth:api')->group(function () {
    });
});

Route::controller(FamiliesController::class)->middleware('auth:api')->group(function () {
    Route::post('family', 'store');

    Route::middleware(['family'])->group(function () {
        Route::post('family/{id}/leave', 'leave');
        Route::get('family/{id}', 'show');
    });

    Route::middleware(['family', 'family-admin'])->group(function () {
        Route::put('family/{id}', 'update');
        Route::delete('family/{id}', 'destroy');

        Route::post('family/{id}/grant', 'authorized');
        Route::post('family/{id}/revoke', 'deauthorized');
    });
});
