<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\TransactionTypeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Resources\BaseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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

Route::middleware('session')->group(function () {

});

/**
 * General Controller is Public API
 */
Route::controller(GeneralController::class)->group(function () {
    Route::prefix("general")->group(function () {
        Route::get('feature-status', 'getFeatureStatus');
        Route::get('system-config', 'getSystemConfig');
    });
});

Route::controller(AuthController::class)->prefix("auth")->group(function () {
    Route::post('pre-register', 'preRegister');
    Route::post('register', 'store');
    Route::post('login', 'login');

    Route::post("forgot-password", 'forgotPassword');
    Route::post("reset-password", 'resetPassword');

    /**
     * Private API for authenticated users
     */
    Route::middleware("auth:api")->group(function () {
        Route::post('logout', 'logout');
        Route::post('refresh-token', 'refreshToken');

        Route::post('pre-change-password', 'preChangePassword');
        Route::post('change-password', 'changePassword');

        /**
         * Pin Controller is Private API, PIN related for authentication
         */
        Route::controller(PinController::class)->group(function () {
            Route::post('pin', 'store');
            Route::post('pin/init', 'init');
            Route::delete('pin', 'destroy');
            Route::post('pin/verify', 'verify');
            Route::post('pin/forgot', 'forgot');
            Route::post('pin/reset', 'reset');
        });
    });
});

Route::controller(OtpController::class)->prefix("otp")->group(function () {
    /**
     * Public API OTP don't need authentication
     */
    Route::post('send/register', 'sendRegister');
    Route::post('send/forgot-password', 'sendForgotPassword');

    Route::middleware('auth:api')->group(function () {
        Route::post("send/change-password", 'sendChangePassword');
        Route::post("send/pin", 'sendPin');
        Route::post("send/forgot-pin", 'sendForgotPin');
        Route::post("send/change-secret-key", 'sendChangeSecretKey');
    });
});

Route::controller(UserController::class)->prefix("user")->middleware('auth:api')->group(function () {
    Route::get('/', 'getProfile');
    Route::put('/', 'updateProfile');
    Route::put('date', 'updateDate');
    Route::post('avatar', 'updateAvatar');
    Route::prefix("secret")->group(function () {
        Route::post('pre-generate', 'preGenerateSecretKey');
        Route::post('generate', 'generateSecretKey');
    });
});

Route::controller(CategoryController::class)->group(function () {
    Route::get('categories', 'getCategories');
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

Route::controller(FamilyController::class)->prefix('family')->middleware('auth:api')->group(function () {
    Route::post('/', 'store');

    Route::post('join', 'responseInvitation');

    Route::middleware(['family'])->group(function () {
        Route::post('{id}/leave', 'leave');
        Route::get('{id}', 'show');
        Route::get('{id}/member', 'getFamilyMember');
        Route::post('{id}/validate', 'validateSecretKey');
    });

    Route::middleware(['family', 'family-admin'])->group(function () {
        Route::put('{id}', 'update');
//        Route::delete('family/{id}', 'destroy');
        Route::get('{id}/admin', 'getFamilyAdmin');
        Route::post("{id}/admin", 'grantAdmin');
        Route::post("{id}/admin/{userId}/revoke", 'revokeAdmin');
        Route::post('{id}/member/{userId}/revoke', 'revokeMember');

        Route::post('{id}/invite', 'inviteMember');
    });
});

Route::controller(WalletController::class)->middleware(['auth:api'])->group(function () {
    Route::get('wallet', 'index');
    Route::post('wallet', 'store');

    Route::middleware('wallet')->group(function () {
        Route::get("wallet/{id}/member", 'getMember');
    });

    Route::middleware('wallet-admin')->group(function () {
        Route::put('wallet/{id}', 'update');
        Route::post('wallet/{id}/status', 'updateStatus');
        Route::post('wallet/{id}/member', 'addMember');
        Route::post('wallet/{id}/member/{userId}/revoke', 'revokeMember');
    });
});
