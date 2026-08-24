<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    // ─── State file helpers ───────────────────────────────────────────

    protected function statePath(): string
    {
        return storage_path('app/install_state.json');
    }

    protected function readState(): array
    {
        $path = $this->statePath();
        if (!file_exists($path)) {
            return ['highest_step' => 0, 'data' => []];
        }
        return json_decode(file_get_contents($path), true) ?: ['highest_step' => 0, 'data' => []];
    }

    protected function writeState(array $state): void
    {
        $dir = dirname($this->statePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->statePath(), json_encode($state, JSON_PRETTY_PRINT));
    }

    protected function markStepComplete(int $step, array $data = []): void
    {
        $state = $this->readState();
        $state['highest_step'] = max($state['highest_step'], $step);
        foreach ($data as $key => $value) {
            $state['data'][$key] = $value;
        }
        $this->writeState($state);
    }

    protected function clearState(): void
    {
        $path = $this->statePath();
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // ─── Route handlers ───────────────────────────────────────────────

    public function index()
    {
        if ($this->isInstalled()) {
            abort(403, 'Installation already completed.');
        }

        // BUG-021 FIX: Bersihkan state file lama saat user kembali ke halaman awal
        // installer. Ini memastikan credential/password yang mungkin tersimpan dari
        // sesi instalasi sebelumnya yang gagal tidak menumpuk di filesystem.
        // State akan dibuat ulang saat user mulai langkah pertama.
        $this->clearState();

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

        // Enforce sequential progress from persistent state
        $state = $this->readState();
        $maxAllowed = $state['highest_step'] + 1;
        if ($step > $maxAllowed) {
            return redirect()->route('install.step', $maxAllowed);
        }

        $data = ['step' => $step, 'steps' => $this->steps];

        if ($step === 1) {
            $data['phpVersion'] = $this->checkPhpVersion();
            $data['requiredPhpVersion'] = '8.3';
            $data['extensions'] = $this->checkExtensions();
            $data['permissions'] = $this->checkPermissions();
        }

        // Pre-fill forms from persisted state (survives server restart)
        if (isset($state['data']) && is_array($state['data'])) {
            $data['state'] = $state['data'];
        }

        $viewName = 'install.' . $this->steps[$step - 1];
        return view($viewName, $data);
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

    // ─── Step 1: Requirements Check ───────────────────────────────────

    protected function step1(Request $request)
    {
        if ($request->has('test')) {
            return $this->testRequirements();
        }

        $this->markStepComplete(1);

        return redirect()->route('install.step', 2);
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
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $permissions = [];
        foreach ($directories as $name => $path) {
            $permissions[$name] = is_writable($path);
        }

        return $permissions;
    }

    // ─── Step 2: Application Configuration ────────────────────────────
    // Stores config in state file only. .env is written in step 6.
    // SettingsService writes deferred to step 6 (single DB write point).

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

            // Store in persistent state — .env write deferred to step 6
            $this->markStepComplete(2, [
                'app' => $validated,
            ]);

            // Set in-memory config so the current request uses the values
            config(['app.name' => $validated['app_name']]);
            config(['app.url' => $validated['app_url']]);
            config(['app.timezone' => $validated['timezone']]);
            config(['app.locale' => $validated['locale']]);
            config(['app.currency' => $validated['currency']]);

            return redirect()->route('install.step', 3);
        }

        return view('install.application');
    }

    // ─── Step 3: Database Configuration ───────────────────────────────
    // Validates connection, stores creds in state file, and runs migrations
    // using the in-memory DB config (.env is not touched until step 6).
    // Tables must exist before step 4/5 write users, roles and settings.

    protected function step3(Request $request)
    {
        if ($request->has('test')) {
            return $this->testDatabaseConnection($request);
        }

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'db_host' => 'required|string|max:255',
                'db_port' => 'required|integer|min:1|max:65535',
                // VERIFY-001: identifiers are interpolated into DSN/USE statements —
                // only allow safe characters.
                'db_database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_$]+$/'],
                'db_username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_$]+$/'],
                'db_password' => ['nullable', 'string', 'max:255'],
            ]);

            // Store in persistent state — .env write deferred to step 6
            $this->markStepComplete(3, [
                'db' => $validated,
            ]);

            // Configure DB connection in-memory (no .env touch)
            config(['database.connections.mysql.host' => $validated['db_host']]);
            config(['database.connections.mysql.port' => $validated['db_port']]);
            config(['database.connections.mysql.database' => $validated['db_database']]);
            config(['database.connections.mysql.username' => $validated['db_username']]);
            config(['database.connections.mysql.password' => $validated['db_password']]);

            // Purge any cached connection and reconnect with new config
            DB::purge('mysql');

            // Run migrations now so steps 4-6 can use the tables.
            // Seeding stays deferred to step 6 to avoid duplicate seeds.
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $isTableExists = str_contains($message, '42S01') || stripos($message, 'already exists') !== false;

                return back()->withErrors([
                    'db_error' => $isTableExists
                        ? 'Database already contains tables from a previous installation. Reset the database (drop all tables) and try again.'
                        : $message,
                ]);
            }

            return redirect()->route('install.step', 4);
        }

        return view('install.database');
    }

    public function testDatabaseConnection(Request $request)
    {
        try {
            $host = $request->db_host ?? 'localhost';
            $port = $request->db_port ?? 3306;
            $database = $request->db_database ?? '';
            $username = $request->db_username ?? '';
            $password = $request->db_password ?? '';

            // VERIFY-001: reject identifier injection before building the DSN/USE
            if ($host === '' || !preg_match('/^[A-Za-z0-9_.\-:\[\]]+$/', (string) $host)) {
                throw new \InvalidArgumentException('DB_HOST tidak valid.');
            }
            if ($database !== '' && !preg_match('/^[A-Za-z0-9_$]+$/', (string) $database)) {
                throw new \InvalidArgumentException('Nama database tidak valid.');
            }
            if (!preg_match('/^[A-Za-z0-9_$]+$/', (string) $username)) {
                throw new \InvalidArgumentException('Username database tidak valid.');
            }

            $dsn = "mysql:host={$host};port={$port}";
            $pdo = new \PDO($dsn, $username, $password);

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

    // ─── Step 4: Admin Creation ───────────────────────────────────────
    // DB is available via in-memory config from step 3. No .env needed.

    protected function step4(Request $request)
    {
        // Re-establish DB config from state in case server restarted
        // between step 3 and step 4 (session survived, in-memory config lost).
        $this->restoreDbConfigFromState();

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'name' => 'required|string|min:3|max:100',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin', 'name' => 'Super Admin']);
            $user->roles()->attach($superAdminRole->id, ['model_type' => User::class]);

            \Illuminate\Support\Facades\Auth::login($user);

            // BUG-003 FIX: Jangan simpan password admin di state file (plaintext).
            // Hanya simpan flag bahwa admin sudah dibuat dan email-nya saja.
            $this->markStepComplete(4, [
                'admin_created' => true,
                'admin' => [
                    'name'  => $validated['name'],
                    'email' => $validated['email'],
                    // password TIDAK disimpan ke state file
                ],
            ]);

            return redirect()->route('install.step', 5);
        }

        return view('install.admin');
    }

    // ─── Step 5: Website Configuration ────────────────────────────────
    // Settings table exists (migrated in step 3). Can use SettingsService.

    protected function step5(Request $request)
    {
        $this->restoreDbConfigFromState();

        if ($request->isMethod('POST')) {
            $validated = $request->validate([
                'site_name' => 'required|string|max:100',
                'site_tagline' => 'nullable|string|max:200',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'accent_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            ]);

            SettingsService::set('site_tagline', $validated['site_tagline'] ?? '');
            SettingsService::set('email', $validated['email'] ?? '');
            SettingsService::set('phone', $validated['phone'] ?? '');
            SettingsService::set('whatsapp_default', $validated['whatsapp'] ?? '');
            SettingsService::set('address', $validated['address'] ?? '');
            SettingsService::set('primary_color', $validated['primary_color']);
            SettingsService::set('secondary_color', $validated['secondary_color']);
            SettingsService::set('accent_color', $validated['accent_color']);

            SettingsService::set('active_theme', 'modern');
            SettingsService::set('theme_primary_color', $validated['primary_color']);
            SettingsService::set('theme_secondary_color', $validated['secondary_color']);
            SettingsService::set('theme_accent_color', $validated['accent_color']);

            $this->markStepComplete(5, [
                'website' => $validated,
            ]);

            return redirect()->route('install.step', 6);
        }

        return view('install.website');
    }

    // ─── Step 6: Finish Installation ──────────────────────────────────
    // Runs migrations. Writes .env from accumulated state.
    // Creates installed.lock FIRST (so retry succeeds).
    // Returns JSON so frontend can show "complete → redirecting" message.

    protected function step6(Request $request)
    {
        if ($request->isMethod('POST')) {
            $state = $this->readState();
            $data = $state['data'] ?? [];

            // ── Restore DB config + run migrations ──
            $this->restoreDbConfigFromState();

            try {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed');
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $isTableExists = str_contains($message, '42S01') || stripos($message, 'already exists') !== false;

                return response()->json([
                    'success' => false,
                    'error' => $message,
                    'fresh_install_available' => $isTableExists,
                ]);
            }

            // Guard against databases migrated from an older code state where
            // migrations were recorded but the schema drifted (e.g. missing
            // settings.group column). migrate reports nothing pending, so we
            // must verify the schema matches before writing settings.
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'group')) {
                return response()->json([
                    'success' => false,
                    'error' => 'The database schema is out of sync with the application migrations (settings table is missing the group column). Reset the database to rebuild it.',
                    'fresh_install_available' => true,
                ]);
            }

            // ── Write deferred SettingsService values from step 2 ──
            if (isset($data['app'])) {
                SettingsService::set('site_name', $data['app']['app_name']);
                SettingsService::set('site_url', $data['app']['app_url']);
                SettingsService::set('timezone', $data['app']['timezone']);
                SettingsService::set('locale', $data['app']['locale']);
                SettingsService::set('currency', $data['app']['currency']);
            }

            // ── Write .env (all accumulated config) ──
            $envPath = base_path('.env');
            $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

            if (isset($data['app'])) {
                $envContent = $this->updateEnvValue($envContent, 'APP_NAME', $data['app']['app_name']);
                $envContent = $this->updateEnvValue($envContent, 'APP_URL', $data['app']['app_url']);
                $envContent = $this->updateEnvValue($envContent, 'APP_TIMEZONE', $data['app']['timezone']);
                $envContent = $this->updateEnvValue($envContent, 'APP_LOCALE', $data['app']['locale']);
                $envContent = $this->updateEnvValue($envContent, 'APP_CURRENCY', $data['app']['currency']);
            }

            if (isset($data['db'])) {
                $envContent = $this->updateEnvValue($envContent, 'DB_HOST', $data['db']['db_host']);
                $envContent = $this->updateEnvValue($envContent, 'DB_PORT', (string) $data['db']['db_port']);
                $envContent = $this->updateEnvValue($envContent, 'DB_DATABASE', $data['db']['db_database']);
                $envContent = $this->updateEnvValue($envContent, 'DB_USERNAME', $data['db']['db_username']);
                $envContent = $this->updateEnvValue($envContent, 'DB_PASSWORD', $data['db']['db_password'] ?? '');
            }

            // ── Create installed.lock BEFORE .env write ──
            // This ensures that even if the server restart kills this process
            // mid-response, the next request sees the lock and bypasses installer.
            file_put_contents(storage_path('installed.lock'), json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'locked' => true,
            ]));

            // ── Write .env — this triggers artisan serve restart ──
            file_put_contents($envPath, $envContent);

            // ── Clean up state file ──
            $this->clearState();

            // ── Clear caches ──
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Return JSON — the response goes out before artisan serve
            // detects the .env change and kills the process.
            $isBuiltInServer = php_sapi_name() === 'cli-server';

            return response()->json([
                'success' => true,
                'redirect' => route('dashboard'),
                'restarting' => $isBuiltInServer,
                'message' => $isBuiltInServer
                    ? 'Installation complete! Server is restarting. Redirecting in 4 seconds…'
                    : 'Installation complete! Redirecting…',
            ]);
        }

        return view('install.finish');
    }

    // ─── Step Fresh: Reset database and reinstall ─────────────────────
    // Called when migration fails due to table conflicts.
    // Runs migrate:fresh, seeds, creates admin, writes .env, creates lock.

    public function stepFresh(Request $request)
    {
        if ($this->isInstalled()) {
            abort(403, 'Installation already completed.');
        }

        $state = $this->readState();
        $data = $state['data'] ?? [];

        $this->restoreDbConfigFromState();

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        // BUG-003 FIX: stepFresh tidak lagi membaca password dari state file.
        // Password tidak pernah disimpan di state (sejak perbaikan step4).
        // Jika admin sudah dibuat di step4 dan masih login, gunakan sesi yang ada.
        // Jika admin belum ada (fresh reset), buat dengan password sementara yang harus diganti.
        $adminData = $data['admin'] ?? null;
        if ($adminData && !empty($adminData['email'])) {
            $existingUser = User::where('email', $adminData['email'])->first();
            if (!$existingUser) {
                // Buat admin dengan password acak — user harus reset via forgot-password
                $tempPassword = Str::random(24);
                $user = User::create([
                    'name'     => $adminData['name'] ?? 'Admin',
                    'email'    => $adminData['email'],
                    'password' => Hash::make($tempPassword),
                ]);
                $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin', 'name' => 'Super Admin']);
                $user->roles()->attach($superAdminRole->id, ['model_type' => User::class]);
            }
        }

        // Write SettingsService values
        if (isset($data['app'])) {
            SettingsService::set('site_name', $data['app']['app_name']);
            SettingsService::set('site_url', $data['app']['app_url']);
            SettingsService::set('timezone', $data['app']['timezone']);
            SettingsService::set('locale', $data['app']['locale']);
            SettingsService::set('currency', $data['app']['currency']);
        }

        // Write .env
        $envPath = base_path('.env');
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

        if (isset($data['app'])) {
            $envContent = $this->updateEnvValue($envContent, 'APP_NAME', $data['app']['app_name']);
            $envContent = $this->updateEnvValue($envContent, 'APP_URL', $data['app']['app_url']);
            $envContent = $this->updateEnvValue($envContent, 'APP_TIMEZONE', $data['app']['timezone']);
            $envContent = $this->updateEnvValue($envContent, 'APP_LOCALE', $data['app']['locale']);
            $envContent = $this->updateEnvValue($envContent, 'APP_CURRENCY', $data['app']['currency']);
        }

        if (isset($data['db'])) {
            $envContent = $this->updateEnvValue($envContent, 'DB_HOST', $data['db']['db_host']);
            $envContent = $this->updateEnvValue($envContent, 'DB_PORT', (string) $data['db']['db_port']);
            $envContent = $this->updateEnvValue($envContent, 'DB_DATABASE', $data['db']['db_database']);
            $envContent = $this->updateEnvValue($envContent, 'DB_USERNAME', $data['db']['db_username']);
            $envContent = $this->updateEnvValue($envContent, 'DB_PASSWORD', $data['db']['db_password'] ?? '');
        }

        // Write website settings if available
        if (isset($data['website'])) {
            $w = $data['website'];
            SettingsService::set('site_tagline', $w['site_tagline'] ?? '');
            SettingsService::set('email', $w['email'] ?? '');
            SettingsService::set('phone', $w['phone'] ?? '');
            SettingsService::set('whatsapp_default', $w['whatsapp'] ?? '');
            SettingsService::set('address', $w['address'] ?? '');
            SettingsService::set('primary_color', $w['primary_color']);
            SettingsService::set('secondary_color', $w['secondary_color']);
            SettingsService::set('accent_color', $w['accent_color']);
            SettingsService::set('active_theme', 'modern');
            SettingsService::set('theme_primary_color', $w['primary_color']);
            SettingsService::set('theme_secondary_color', $w['secondary_color']);
            SettingsService::set('theme_accent_color', $w['accent_color']);
        }

        // Create installed.lock
        file_put_contents(storage_path('installed.lock'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'locked' => true,
        ]));

        file_put_contents($envPath, $envContent);

        $this->clearState();

        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $isBuiltInServer = php_sapi_name() === 'cli-server';

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard'),
            'restarting' => $isBuiltInServer,
            'message' => $isBuiltInServer
                ? 'Fresh install complete! Server is restarting. Redirecting in 4 seconds…'
                : 'Fresh install complete! Redirecting…',
        ]);
    }

    // ─── .env helper ──────────────────────────────────────────────────

    protected function updateEnvValue(string $content, string $key, string $value): string
    {
        $escapedKey = preg_quote($key, '/');
        $pattern = "/^{$escapedKey}=.*/m";
        $replacement = "{$key}=\"{$value}\"";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replacement, $content);
        }

        return $content . "\n{$replacement}";
    }

    // ─── Restore DB config from state file ────────────────────────────
    // Called at the start of step 4 and step 5 to handle the case where
    // the server restarted between steps and in-memory config was lost.

    protected function restoreDbConfigFromState(): void
    {
        $state = $this->readState();
        $db = $state['data']['db'] ?? null;

        if ($db) {
            config(['database.connections.mysql.host' => $db['db_host']]);
            config(['database.connections.mysql.port' => $db['db_port']]);
            config(['database.connections.mysql.database' => $db['db_database']]);
            config(['database.connections.mysql.username' => $db['db_username']]);
            config(['database.connections.mysql.password' => $db['db_password'] ?? '']);
            DB::purge('mysql');
        }
    }
}
