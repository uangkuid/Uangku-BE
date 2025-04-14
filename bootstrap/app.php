<?php

use App\Http\Middleware\AutoSelectAuthMiddleware;
use App\Http\Middleware\FamilyAdminMiddleware;
use App\Http\Middleware\FamilyMiddleware;
use App\Http\Middleware\PersonalMiddleware;
use App\Http\Middleware\SessionMiddleware;
use App\Http\Middleware\WalletAdminMiddleware;
use App\Http\Middleware\WalletMiddleware;
use App\Http\Resources\BaseResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('family', [
            FamilyMiddleware::class,
        ]);
        $middleware->appendToGroup('family-admin', [
            FamilyAdminMiddleware::class
        ]);
        $middleware->appendToGroup('personal', [
            PersonalMiddleware::class
        ]);
        $middleware->appendToGroup('wallet', [
            WalletMiddleware::class
        ]);
        $middleware->appendToGroup('wallet-admin', [
            WalletAdminMiddleware::class
        ]);
        $middleware->appendToGroup('auto-auth', [
            AutoSelectAuthMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(new BaseResponse(
                    401,
                    $e->getMessage(),
                    null
                ), 401);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
           if($request->is('api/*')) {
               return response()->json(new BaseResponse(
                   404,
                   $e->getMessage()
               ), 404);
           }
        });
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(new BaseResponse(
                    500,
                    $e->getMessage()
                ));
            }
        });
    })->create();
