<?php declare(strict_types=1);

use Illuminate\Routing\Route;
use Enlight\LaravelInstaller\Controllers\UpdateController;
use Enlight\LaravelInstaller\Controllers\WelcomeController;
use Enlight\LaravelInstaller\Controllers\DatabaseController;
use Enlight\LaravelInstaller\Controllers\EnvironmentController;
use Enlight\LaravelInstaller\Controllers\PermissionsController;

Route::group(['namespace' => 'Enlight\LaravelInstaller\Controllers'], function () {
    Route::group(['middleware' => ['web', 'install']], function () {
        Route::get('error/{var}', [WelcomeController::class, 'error']);
    });

    Route::group(['prefix' => 'install', 'as' => 'LaravelInstaller::', 'middleware' => ['web', 'install']], function () {
        Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');

        Route::get('environment', [EnvironmentController::class, 'environmentMenu'])->name('environment');

        Route::get('environment/wizard', [EnvironmentController::class, 'environmentWizard'])->name('environmentWizard');

        Route::post('environment/saveWizard', [
            'as' => 'environmentSaveWizard',
            'uses' => 'EnvironmentController@saveWizard'
        ]);

        Route::get('environment/classic', [
            'as' => 'environmentClassic',
            'uses' => 'EnvironmentController@environmentClassic'
        ]);

        Route::post('environment/saveClassic', [
            'as' => 'environmentSaveClassic',
            'uses' => 'EnvironmentController@saveClassic'
        ]);

        Route::get('requirements', [
            'as' => 'requirements',
            'uses' => 'RequirementsController@requirements'
        ]);

        Route::get('permissions', [PermissionsController::class, 'permissions'])->name('permissions');
        Route::get('database', [DatabaseController::class, 'database'])->name('database');

        Route::get('final', [
            'as' => 'final',
            'uses' => 'FinalController@finish'
        ]);
    });

    Route::group(['prefix' => 'update', 'as' => 'LaravelUpdater::', 'middleware' => 'web'], function () {
        Route::group(['middleware' => 'update'], function () {
            Route::get('/', [UpdateController::class, 'welcome'])->name('welcome');
            Route::get('overview', [UpdateController::class, 'overview'])->name('overview');
            Route::get('database', [UpdateController::class, 'database'])->name('database');
        });

        // This needs to be out of the middleware because right after the migration has been
        // run, the middleware sends a 404.
        Route::get('final', [UpdateController::class, 'finish'])->name('final');
    });
});
