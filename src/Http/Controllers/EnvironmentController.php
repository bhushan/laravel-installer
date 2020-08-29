<?php declare(strict_types=1);

namespace Enlight\LaravelInstaller\Controllers;

use Illuminate\Http\RedirectResponse;
use Validator;
use App\Models\Core\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Redirector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Enlight\LaravelInstaller\Events\EnvironmentSaved;
use Enlight\LaravelInstaller\Helpers\DatabaseManager;
use Enlight\LaravelInstaller\Helpers\EnvironmentManager;

class EnvironmentController extends Controller
{
    /**
     * @var EnvironmentManager
     */
    protected $EnvironmentManager;
    private $databaseManager;
    private $ticketRepository;
    private $api_url = 'http://api.themes-coder.com';

    /**
     * @param EnvironmentManager $environmentManager
     */
    public function __construct(EnvironmentManager $environmentManager, DatabaseManager $databaseManager)
    {
        $this->EnvironmentManager = $environmentManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Display the Environment menu page.
     *
     * @return View
     */
    public function environmentMenu()
    {
        return view('vendor.installer.environment')->with('error', 0)->with('msg', '');
    }

    protected function curl($url)
    {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    /**
     * Display the Environment page.
     *
     * @return View
     */
    public function environmentWizard()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();

        return view('vendor.installer.environment-wizard', compact('envConfig'))->with('error', 0)->with('msg', '');
    }

    /**
     * Display the Environment page.
     *
     * @return View
     */
    public function environmentClassic()
    {
        $envConfig = $this->EnvironmentManager->getEnvContent();

        return view('vendor.installer.environment-classic', compact('envConfig'));
    }

    /**
     * Processes the newly saved environment configuration (Classic).
     *
     * @param Request $input
     * @param Redirector $redirect
     * @return RedirectResponse
     */
    public function saveClassic(Request $input, Redirector $redirect)
    {
        $message = $this->EnvironmentManager->saveFileClassic($input);

        event(new EnvironmentSaved($input));

        return $redirect->route('LaravelInstaller::environmentClassic')
            ->with(['message' => $message]);
    }

    /**
     * Processes the newly saved environment configuration (Form Wizard).
     *
     * @param Request $request
     * @param Redirector $redirect
     * @return RedirectResponse
     */
    public function saveWizard(Request $request, Redirector $redirect)
    {
        try {
            $rules = ['rules' => [
                'app_name' => 'required|string|max:50',
                'environment' => 'required|string|max:50',
                'environment_custom' => 'required_if:environment,other|max:50',
                'app_debug' => [
                    Rule::in(['true', 'false']),
                ]
            ],
                'app_log_level' => 'required|string|max:50',
                'app_url' => 'required|url',
                'database_connection' => 'required|string|max:50',
                'database_hostname' => 'required|string|max:50',
                'database_port' => 'required|numeric',
                'database_name' => 'required|string|max:50',
                'database_username' => 'string|max:50',
                'broadcast_driver' => 'string|max:50',
                'cache_driver' => 'string|max:50',
                'session_driver' => 'string|max:50',
                'queue_driver' => 'string|max:50',
                'redis_hostname' => 'string|max:50',
                'redis_password' => 'string|max:50',
                'redis_port' => 'numeric',
                'mail_driver' => 'required',
                'mail_host' => 'required',
                'mail_port' => 'required',
                'mail_username' => 'required',
                'mail_password' => 'required',
                'mail_encryption' => 'required',
                'pusher_app_id' => 'max:50',
                'pusher_app_key' => 'max:50',
                'pusher_app_secret' => 'max:50',
                'user_name' => 'required|string|max:50',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'email' => 'required|email',
                'password' => 'required||min:5|confirmed',
                'purchase_code' => 'required',
                // 'database_password'     => 'required'
            ];

            $messages = [
                'environment_custom.required_if' => 'Ops!! Something Went Wrong!',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return redirect()->back()->withInput($request->all())->withErrors($validator->errors());
            }

            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                unlink(base_path('bootstrap/cache/config.php'));
            }
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            $results = $this->EnvironmentManager->saveFileWizard($request);
            Artisan::call('config:cache');
            $this->databaseManager->migrateAndSeed();

            //store admin
            $user = new User();
            $data = [
                'user_name' => $request->user_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password
            ];

            $user->saveAdmin($data);
            event(new EnvironmentSaved($request));

            return $redirect->route('LaravelInstaller::database')
                ->with(['results' => $results]);
        } catch (\Exception $e) {
            $msg = $e->getCode();

            if ($e->getCode() === '1045') {
                return $redirect->to('error/' . $msg);
            } else {
                echo $e->getMessage();
            }
        }
    }
}
