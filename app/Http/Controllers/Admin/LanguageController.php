<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('sort_order')->get();
        return view('admin.languages.index', compact('languages'));
    }

    public function create()
    {
        return view('admin.languages.create', ['language' => new Language]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:10|unique:languages,code',
            'name'        => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'flag_emoji'  => 'nullable|string|max:10',
            'flag_code'   => 'nullable|string|max:10',
            'is_active'   => 'boolean',
            'is_default'  => 'boolean',
            'sort_order'  => 'integer',
        ]);
        $data['code']       = strtolower($data['code']);
        $data['flag_code']  = strtoupper($data['flag_code'] ?? '');
        $data['is_active']  = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        Language::create($data);
        return redirect()->route('admin.languages.index')->with('success', 'Bahasa berhasil ditambahkan.');
    }

    public function edit(Language $language)
    {
        return view('admin.languages.edit', compact('language'));
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:10|unique:languages,code,' . $language->id,
            'name'        => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'flag_emoji'  => 'nullable|string|max:10',
            'flag_code'   => 'nullable|string|max:10',
            'is_active'   => 'boolean',
            'is_default'  => 'boolean',
            'sort_order'  => 'integer',
        ]);
        $data['code']       = strtolower($data['code']);
        $data['flag_code']  = strtoupper($data['flag_code'] ?? '');
        $data['is_active']  = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            Language::where('is_default', true)->where('id', '!=', $language->id)->update(['is_default' => false]);
        }

        $language->update($data);
        return redirect()->route('admin.languages.index')->with('success', 'Bahasa berhasil diperbarui.');
    }

    public function destroy(Language $language)
    {
        if ($language->is_default) {
            return back()->with('error', 'Bahasa default tidak bisa dihapus.');
        }
        $language->delete();
        return redirect()->route('admin.languages.index')->with('success', 'Bahasa dihapus.');
    }

    /**
     * Show the per-string translation editor for a language.
     * Loads the target language's lang/{code}.json merged with the default
     * (base) language keys so untranslated keys still appear as empty fields.
     */
    public function editTranslations(Language $language)
    {
        $code = $this->sanitizeCode($language->code);

        $target  = $this->readTranslationFile($code);
        $default = Language::default();
        $baseCode = $default ? $this->sanitizeCode($default->code) : null;
        $base    = ($baseCode && $baseCode !== $code) ? $this->readTranslationFile($baseCode) : [];

        // Merge: every base key becomes visible (empty if untranslated),
        // and any extra keys already present in the target are preserved.
        $keys = array_unique(array_merge(array_keys($base), array_keys($target)));
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        $translations = [];
        foreach ($keys as $key) {
            $translations[] = [
                'key'       => $key,
                'value'     => $target[$key] ?? '',
                'reference' => $base[$key] ?? null,
            ];
        }

        return view('admin.languages.translations', [
            'language'     => $language,
            'translations' => $translations,
            'baseLanguage' => ($baseCode && $baseCode !== $code) ? $default : null,
            'totalKeys'    => count($translations),
        ]);
    }

    /**
     * Persist edited translation strings back to lang/{code}.json.
     * Accepts a single JSON payload field ("payload") to avoid PHP's
     * max_input_vars limit when there are hundreds of keys.
     */
    public function updateTranslations(Request $request, Language $language)
    {
        $code = $this->sanitizeCode($language->code);

        $request->validate([
            'payload' => 'required|string',
        ]);

        $decoded = json_decode($request->input('payload'), true);
        if (!is_array($decoded)) {
            return back()->with('error', __('lang.invalid_payload'))->withInput();
        }

        // Sanitize: keys must be non-empty strings, values coerced to strings.
        $clean = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $clean[$key] = (string) ($value ?? '');
        }

        // Preserve natural sort for readability.
        ksort($clean, SORT_NATURAL | SORT_FLAG_CASE);

        $path = $this->translationPath($code);

        $json = json_encode(
            (object) $clean,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return back()->with('error', __('lang.invalid_payload'))->withInput();
        }

        // Atomic write: write to a temp file then rename into place.
        $tmp = $path . '.' . uniqid('tmp', true);
        if (@file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) {
            @unlink($tmp);
            return back()->with('error', __('lang.write_failed'))->withInput();
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return back()->with('error', __('lang.write_failed'))->withInput();
        }

        // Clear compiled/cached translation state where applicable.
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            // Non-fatal; the file has already been written.
        }

        return redirect()
            ->route('admin.languages.translations', $language)
            ->with('success', __('lang.translations_saved'));
    }

    /**
     * Sanitize a language code to a safe locale token.
     * Matches lowercase ISO-639 with an optional region suffix.
     */
    private function sanitizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        if (!preg_match('/^[a-z]{2,5}(-[a-z]{2})?$/', $code)) {
            abort(404);
        }
        return $code;
    }

    /**
     * Absolute path to a language JSON file, guarded against traversal.
     */
    private function translationPath(string $code): string
    {
        $dir  = base_path('lang');
        $path = $dir . DIRECTORY_SEPARATOR . $code . '.json';

        // Path-traversal guard: resolved path must stay inside lang/.
        $realDir = realpath($dir);
        $realParent = realpath(dirname($path));
        if ($realDir === false || $realParent === false || $realParent !== $realDir) {
            abort(404);
        }

        return $path;
    }

    /**
     * Read and decode a language JSON file. Returns [] if missing/invalid.
     *
     * @return array<string, string>
     */
    private function readTranslationFile(string $code): array
    {
        $path = $this->translationPath($code);
        if (!is_file($path)) {
            return [];
        }
        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }
        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function toggleStatus(Language $language)
    {
        if ($language->is_default && $language->is_active) {
            return response()->json(['success' => false, 'message' => 'Bahasa default tidak bisa dinonaktifkan.'], 422);
        }
        $language->update(['is_active' => !$language->is_active]);
        return response()->json(['success' => true, 'is_active' => $language->is_active]);
    }
}
