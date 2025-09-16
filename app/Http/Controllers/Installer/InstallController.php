<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

use App\Models\User;
use App\Models\Setting;
use App\Models\SiteSetting;

class InstallController extends Controller
{
    /* ----------------------- STEP 0: Welcome ----------------------- */
    public function welcome()
    {
        if ($this->isInstalled())
            return redirect()->route('login');
        return view('installer.welcome');
    }

    /* ------------------- STEP 1: Requirements ---------------------- */
    public function requirements()
    {
        if ($this->isInstalled())
            return redirect()->route('login');

        $checks = [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'ext:openssl' => extension_loaded('openssl'),
            'ext:mbstring' => extension_loaded('mbstring'),
            'ext:tokenizer' => extension_loaded('tokenizer'),
            'ext:xml' => extension_loaded('xml'),
            'ext:ctype' => extension_loaded('ctype'),
            'ext:json' => extension_loaded('json'),
            'ext:pdo' => extension_loaded('pdo'),
            'ext:bcmath' => extension_loaded('bcmath'),
            'ext:gd' => extension_loaded('gd'),
            'ext:fileinfo' => extension_loaded('fileinfo'),
            'writable:storage/framework/cache' => $this->isWritable(storage_path('framework/cache')),
            'writable:storage/framework/sessions' => $this->isWritable(storage_path('framework/sessions')),
            'writable:bootstrap/cache' => $this->isWritable(base_path('bootstrap/cache')),
            'writable:.env' => $this->isWritable(base_path('.env')) || !file_exists(base_path('.env')),
        ];

        $allPassed = !in_array(false, array_values($checks), true);

        return view('installer.requirements', compact('checks', 'allPassed'));
    }

    public function requirementsNext(Request $request)
    {
        return redirect()->route('install.database');
    }

    /* ------------------- STEP 2: Database setup -------------------- */
    public function database()
    {
        if ($this->isInstalled())
            return redirect()->route('login');

        $defaults = [
            'DB_HOST' => env('DB_HOST', '127.0.0.1'),
            'DB_PORT' => env('DB_PORT', '3306'),
            'DB_DATABASE' => env('DB_DATABASE', ''),
            'DB_USERNAME' => env('DB_USERNAME', 'root'),
            'DB_PASSWORD' => env('DB_PASSWORD', ''),
        ];

        return view('installer.database', compact('defaults'));
    }

    public function databaseTest(Request $request)
    {
        $data = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_name' => 'required|string',
            'db_user' => 'required|string',
            'db_pass' => 'nullable|string',
        ]);

        $ok = $this->testDbConnection(
            $data['db_host'],
            $data['db_port'],
            $data['db_name'],
            $data['db_user'],
            $data['db_pass'] ?? ''
        );

        return response()->json(['ok' => $ok]);
    }

    public function databaseSave(Request $request)
    {
        $data = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_name' => 'required|string',
            'db_user' => 'required|string',
            'db_pass' => 'nullable|string',
        ]);

        // Test connection first
        if (!$this->testDbConnection($data['db_host'], $data['db_port'], $data['db_name'], $data['db_user'], $data['db_pass'] ?? '')) {
            return back()->withErrors(['db' => 'Cannot connect to the database with provided credentials.'])->withInput();
        }

        // Persist to .env
        $this->setEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_name'],
            'DB_USERNAME' => $data['db_user'],
            'DB_PASSWORD' => $data['db_pass'] ?? '',
        ]);

        // Store in session for later steps
        session()->put('installer.db', $data);

        return redirect()->route('install.admin');
    }

    /* -------------------- STEP 3: Admin account -------------------- */
    public function admin()
    {
        if ($this->isInstalled())
            return redirect()->route('login');
        return view('installer.admin');
    }

    public function adminSave(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        session()->put('installer.admin', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return redirect()->route('install.site');
    }

    /* --------------------- STEP 4: Site settings ------------------- */
    public function site()
    {
        if ($this->isInstalled())
            return redirect()->route('login');

        $defaults = [
            'title' => env('APP_NAME', 'Laracms'),
            'url' => env('APP_URL', url('/')),
        ];
        return view('installer.site', compact('defaults'));
    }

    public function siteSave(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|min:2|max:120',
            'url' => 'required|url',
        ]);

        // Save temporarily; actual DB write happens on finish
        session()->put('installer.site', $data);

        // Update .env APP_NAME/APP_URL early (harmless)
        $this->setEnv([
            'APP_NAME' => $data['title'],
            'APP_URL' => rtrim($data['url'], '/'),
        ]);

        return redirect()->route('install.finish');
    }

    /* --------------------- STEP 5: Finish install ------------------ */
    public function finish()
    {
        if ($this->isInstalled())
            return redirect()->route('login');
        return view('installer.finish');
    }

    public function finishRun(Request $request)
    {
        if ($this->isInstalled())
            return redirect()->route('login');

        // 1) Run migrations
        Artisan::call('migrate', ['--force' => true]);

        // 2) Ensure APP_KEY exists
        if (empty(env('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // 3) Create admin user from session
        $admin = session('installer.admin');
        if ($admin) {
            $user = User::firstOrNew(['email' => $admin['email']]);
            $user->name = $admin['name'];
            $user->password = Hash::make($admin['password']);
            // Your project has an is_admin flag migration:
            if (schema()->hasColumn('users', 'is_admin')) {
                $user->is_admin = true;
            }
            $user->save();
        }

        // 4) Save site settings (both systems for compatibility)
        $site = session('installer.site');
        if ($site) {
            // Key/value settings table
            Setting::setValue('general.site_title', $site['title'], 'general');
            Setting::setValue('general.site_url', rtrim($site['url'], '/'), 'general');

            // SiteSetting row (used elsewhere in your project)
            if (class_exists(SiteSetting::class)) {
                SiteSetting::query()->firstOrCreate([], [
                    'site_name' => $site['title'],
                    'options' => ['footer_text' => '© ' . date('Y') . ' ' . $site['title']],
                ]);
            }
        }

        // 5) Mark installed in .env
        $this->setEnv([
            'APP_INSTALLED' => 'true',
            'installed' => 'true',   // keep both for compatibility with your prompt
        ]);

        // 6) Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        // Drop session state for installer
        session()->forget(['installer.db', 'installer.admin', 'installer.site']);

        // Go to login
        return redirect()->to('/login')->with('status', 'Installation complete! Please log in.');
    }

    /* --------------------------- Helpers --------------------------- */

    private function isInstalled(): bool
    {
        return (bool) (env('APP_INSTALLED', false) || env('installed', false));
    }

    private function isWritable(string $path): bool
    {
        if (!file_exists($path))
            return is_writable(dirname($path));
        return is_writable($path);
    }

    private function testDbConnection($host, $port, $database, $username, $password): bool
    {
        try {
            $cfg = [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
            ];
            config(['database.connections.install_temporary' => $cfg]);
            DB::connection('install_temporary')->getPdo();
            DB::purge('install_temporary');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function setEnv(array $pairs): void
    {
        $envFile = base_path('.env');
        $content = file_exists($envFile) ? File::get($envFile) : File::get(base_path('.env.example'));

        foreach ($pairs as $key => $value) {
            $value = $this->quoteEnvValue($value);
            if (preg_match("/^{$key}=.*$/m", $content)) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $content);
            } else {
                $content .= PHP_EOL . "{$key}={$value}";
            }
            // update runtime
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        File::put($envFile, $content);
    }

    private function quoteEnvValue($value): string
    {
        $value = (string) $value;
        if (str_contains($value, ' ') || str_contains($value, '#')) {
            return '"' . addcslashes($value, '"') . '"';
        }
        return $value;
    }
}

/* tiny helper; schema() like DB::getSchemaBuilder() */
if (!function_exists('schema')) {
    function schema()
    {
        return \Illuminate\Support\Facades\Schema::getFacadeRoot();
    }
}