<?php

use Illuminate\Support\Facades\Route;
use Kayalous\SocialAuth\App\Http\Controllers\Api\Auth\SocialProvidersController;
Route::prefix('api')->group(function () {

Route::prefix('auth')->group(function () {

    Route::prefix('login')->group(function () {

        Route::get('/{provider}', [SocialProvidersController::class, 'redirectToProvider']);

        Route::get('/{provider}/callback', [SocialProvidersController::class, 'handleProviderCallback']);

    });

});
});
