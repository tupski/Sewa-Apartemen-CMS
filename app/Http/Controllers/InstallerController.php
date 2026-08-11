<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InstallerController extends Controller
{
    protected $steps = [
        'requirements',
        'application',
        'database',
        'admin',
        'website',
        'finish',
    ];

    public function index()
    {
        if ($this->isInstalled()) {
            abort(403, 'Installation already completed.');
        }

        return redirect()->route('install.step', 1);
    }

    public function step(Request $request, $step)
    {
        if ($this->isInstalled()) {
            abort(403, 'Installation already completed.');
        }

        $step = (int) $step;

        if ($step < 1 || $step > count($this->steps)) {
            abort(404);
        }

        return view('install.step', compact('step'));
    }

    public function processStep(Request $request, $step)
    {
        if ($this->isInstalled()) {
            abort(403, 'Installation already completed.');
        }

        $step = (int) $step;

        if ($step < 1 || $step > count($this->steps)) {
            abort(404);
        }

        $method = 'step' . $step;

        if (!method_exists($this, $method)) {
            abort(404);
        }

        return $this->$method($request);
    }

    protected function isInstalled(): bool
    {
        return file_exists(storage_path('installed.lock'));
    }

    // Step 1: Requirements Check
    protected function step1(Request $request)
    {
        if ($request->has('test')) {
            return $this->testRequirements();
        }

        return view('install.requirements');
    }

    protected function testRequirements()
    {
        $requirements = [
            'php' => $this->checkPhpVersion(),
            'extensions' => $this->checkExtensions(),
            'permissions' => $this->checkPermissions(),
        ];

        $passed = $requirements['php'] &&
                  count($requirements['extensions']) === count(array_filter($requirements['extensions'])) &&
                  count($requirements['permissions']) === count(array_filter($requirements['permissions']));

        return response()->json([
            'passed' => $passed,
            'requirements' => $requirements,
        ]);
    }

    protected function checkPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, '8.3.0', '>=');
    }

    protected function checkExtensions(): array
    {
        $extensions = [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'ctype' => extension_loaded('ctype'),
            'json' => extension_loaded('json'),
            'fileinfo' => extension_loaded('fileinfo'),
            'gd' => extension_loaded('gd'),
        ];

        return $extensions;
    }

    protected function checkPermissions(): array
    {
        $directories = [
            'storage' => storage_path(),
            'storage/app/public' => storage_path('app/public'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => bootstrap_path('cache'),
        ];

        $permissions = [];
        foreach ($directories as $name => $path) {
            $permissions[$name] = is_writable($path);
        }

        return $permissions;
    }

    // Step 2: Application Configuration
    protected function step2(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'app_name' => 'required|string|max:100',
                'app_url' => 'required|url',
                'timezone' => 'required|timezone',
                'locale' => 'required|in:en,id,ja,ko,zh',
                'currency' => 'required|string|in:IDR,USD,EUR,GBP,JPY',
            ]);

            // Update .env file
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            $envContent = $this->updateEnvValue($envContent, 'APP_NAME', $validated['app_name']);
            $envContent = $this->updateEnvValue($envContent, 'APP_URL', $validated['app_url']);
            $envContent = $this->updateEnvValue($envContent, 'APP_TIMEZONE', $validated['timezone']);
            $envContent = $this->updateEnvValue($envContent, 'APP_LOCALE', $validated['locale']);
            $envContent = $this->updateEnvValue($envContent, 'APP_CURRENCY', $validated['currency']);

            file_put_contents($envPath, $envContent);

            // Update config cache
            config(['app.name' => $validated['app_name']]);
            config(['app.url' => $validated['app_url']]);
            config(['app.timezone' => $validated['timezone']]);
            config(['app.locale' => $validated['locale']]);
            config(['app.currency' => $validated['currency']]);

            // Store in settings table
            SettingsService::set('site_name', $validated['app_name']);
            SettingsService::set('site_url', $validated['app_url']);
            SettingsService::set('timezone', $validated['timezone']);
            SettingsService::set('locale', $validated['locale']);
            SettingsService::set('currency', $validated['currency']);

            return redirect()->route('install.step', 3);
        }

        return view('install.application');
    }

    protected function updateEnvValue(string $content, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}=\"{$value}\"";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replacement, $content);
        }

        return $content . "\n{$replacement}";
    }

    // Step 3: Database Configuration
    protected function step3(Request $request)
    {
        if ($request->has('test')) {
            return $this->testDatabaseConnection($request);
        }

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'db_host' => 'required|string',
                'db_port' => 'required|integer|min:1|max:65535',
                'db_database' => 'required|string',
                'db_username' => 'required|string',
                'db_password' => 'nullable|string',
            ]);

            // Update .env file
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            $envContent = $this->updateEnvValue($envContent, 'DB_HOST', $validated['db_host']);
            $envContent = $this->updateEnvValue($envContent, 'DB_PORT', $validated['db_port']);
            $envContent = $this->updateEnvValue($envContent, 'DB_DATABASE', $validated['db_database']);
            $envContent = $this->updateEnvValue($envContent, 'DB_USERNAME', $validated['db_username']);
            $envContent = $this->updateEnvValue($envContent, 'DB_PASSWORD', $validated['db_password']);

            file_put_contents($envPath, $envContent);

            // Update config
            config(['database.connections.mysql.host' => $validated['db_host']]);
            config(['database.connections.mysql.port' => $validated['db_port']]);
            config(['database.connections.mysql.database' => $validated['db_database']]);
            config(['database.connections.mysql.username' => $validated['db_username']]);
            config(['database.connections.mysql.password' => $validated['db_password']]);

            // Run migrations
            try {
                Artisan::call('migrate', ['--force' => true]);

                // Seed data
                Artisan::call('db:seed');
            } catch (\Exception $e) {
                return back()->withErrors(['db_error' => $e->getMessage()]);
            }

            return redirect()->route('install.step', 4);
        }

        return view('install.database');
    }

    protected function testDatabaseConnection(Request $request)
    {
        try {
            $host = $request->db_host ?? 'localhost';
            $port = $request->db_port ?? 3306;
            $database = $request->db_database ?? '';
            $username = $request->db_username ?? '';
            $password = $request->db_password ?? '';

            // Try to connect
            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new \PDO($dsn, $username, $password);

            // Try to use database if specified
            if (!empty($database)) {
                $pdo->exec("USE `{$database}`");
            }

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Step 4: Admin Creation
    protected function step4(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'name' => 'required|string|min:3|max:100',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Create admin user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            ]);

            // Assign super admin role
            $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin', 'name' => 'Super Admin']);
            $user->assignRole($superAdminRole);

            // Log in user
            \Illuminate\Support\Facades\Auth::login($user);

            return redirect()->route('install.step', 5);
        }

        return view('install.admin');
    }

    // Step 5: Website Configuration
    protected function step5(Request $request)
    {
        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'site_name' => 'required|string|max:100',
                'site_tagline' => 'nullable|string|max:200',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'primary_color' => 'required|color_code|#3B82F6',
                'secondary_color' => 'required|color_code|#10B981',
                'accent_color' => 'required|color_code|#F59E0B',
            ]);

            // Store settings
            SettingsService::set('site_tagline', $validated['site_tagline'] ?? '');
            SettingsService::set('email', $validated['email'] ?? '');
            SettingsService::set('phone', $validated['phone'] ?? '');
            SettingsService::set('whatsapp_default', $validated['whatsapp'] ?? '');
            SettingsService::set('address', $validated['address'] ?? '');
            SettingsService::set('primary_color', $validated['primary_color']);
            SettingsService::set('secondary_color', $validated['secondary_color']);
            SettingsService::set('accent_color', $validated['accent_color']);

            // Store theme settings
            SettingsService::set('active_theme', 'modern');
            SettingsService::set('theme_primary_color', $validated['primary_color']);
            SettingsService::set('theme_secondary_color', $validated['secondary_color']);
            SettingsService::set('theme_accent_color', $validated['accent_color']);

            return redirect()->route('install.step', 6);
        }

        return view('install.website');
    }

    // Step 6: Finish Installation
    protected function step6(Request $request)
    {
        if ($request->isMethod('POST')) {
            // Create lock file
            file_put_contents(storage_path('installed.lock'), json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'locked' => true,
            ]));

            // Clear cache
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return redirect()->route('admin');
        }

        return view('install.finish');
    }
}
