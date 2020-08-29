<?php declare(strict_types=1);

namespace Enlight\LaravelInstaller\Controllers;

use Illuminate\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Enlight\LaravelInstaller\Helpers\DatabaseManager;
use Enlight\LaravelInstaller\Helpers\EnvironmentManager;
use Enlight\LaravelInstaller\Helpers\FinalInstallManager;
use Enlight\LaravelInstaller\Helpers\InstalledFileManager;
use Enlight\LaravelInstaller\Events\LaravelInstallerFinished;

class FinalController extends Controller
{
    /**
     * Update installed file and display finished view.
     *
     * @param DatabaseManager $databaseManager
     * @param InstalledFileManager $fileManager
     * @param FinalInstallManager $finalInstall
     * @param EnvironmentManager $environment
     * @return View
     */
    public function finish(DatabaseManager $databaseManager, InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {
        $response = $databaseManager->migrateAndSeed();
        $finalMessages = $finalInstall->runFinal();
        if ($response['status'] !== 'error' && $finalMessages === '') {
            if (config('app.url') === 'production') {
                $environments = 'Live';
            } else {
                $environments = 'Maintenance';
            }

            DB::table('settings')->where('name', 'external_website_link')->update(['value' => config('app.url')]);
            DB::table('settings')->where('name', 'app_name')->update(['value' => config('app.name')]);
            DB::table('settings')->where('name', 'environment')->update(['value' => $environments]);

            $finalStatusMessage = $fileManager->update();
        } else {
            $finalStatusMessage = 'Error Check Your Database Credentials. You Might Be something Missing!';
        }
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
