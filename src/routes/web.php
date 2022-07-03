<?php

use Illuminate\Support\Facades\Route;
use Kayalous\SocialAuth\App\Http\Controllers\Auth\SocialProvidersWebController;

Route::prefix('auth')->middleware(['web', 'guest'])->group(function () {

        Route::prefix('login')->group(function () {

            Route::get('/{provider}', [SocialProvidersWebController::class, 'redirectToProvider']);

            Route::get('/{provider}/callback', [SocialProvidersWebController::class, 'handleProviderCallback']);

        });

    });
