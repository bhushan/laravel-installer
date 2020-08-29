<?php declare(strict_types=1);

namespace Enlight\LaravelInstaller\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Foundation\Application;

class WelcomeController extends Controller
{
    /**
     * Display the installer welcome page.
     *
     * @return Application|Factory|Response|View
     */
    public function welcome()
    {
        $val = Session::get('erorrr');
        Session::put(['erorrr' => $val]);

        return view('vendor.installer.welcome', ['error' => 0, 'msg' => '']);
    }

    public function error($var)
    {
        return view('vendor.installer.error', ['error' => $var]);
    }
}
