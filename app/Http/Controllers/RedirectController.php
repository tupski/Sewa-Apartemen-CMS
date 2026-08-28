<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of redirects.
     */
    public function index(Request $request)
    {
        $query = Redirect::query();

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('from_url', 'like', '%'.$request->search.'%')
                    ->orWhere('to_url', 'like', '%'.$request->search.'%');
            });
        }

        $redirects = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.redirects.index', compact('redirects'));
    }

    /**
     * Show the form for creating a new redirect.
     */
    public function create()
    {
        return view('admin.redirects.create');
    }

    /**
     * Store a newly created redirect.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_url' => 'required|string|max:2048|unique:redirects,from_url',
            // BUG-014 FIX: Validasi to_url harus berupa URL yang valid (dimulai /  atau https?://)
            // agar tidak bisa digunakan sebagai open redirector ke situs berbahaya eksternal.
            'to_url' => ['required', 'string', 'max:2048', function ($attribute, $value, $fail) {
                // Izinkan: path relatif (/path/to/page) atau URL absolut ke host yang sama
                // Blokir: javascript:, data:, //evil.com, dll.
                if (! str_starts_with($value, '/') && ! preg_match('#^https?://#i', $value)) {
                    $fail('URL tujuan harus berupa path relatif (/) atau URL lengkap (https://).');
                }
                if (preg_match('#^(javascript|data|vbscript):#i', $value)) {
                    $fail('URL tujuan mengandung protokol yang tidak diizinkan.');
                }
            }],
            'status_code' => 'required|integer|in:301,302',
        ]);

        Redirect::create($validated);

        return redirect()
            ->route('admin.redirects.index')
            ->with('success', 'Redirect created successfully.');
    }

    /**
     * Show the form for editing the specified redirect.
     */
    public function edit(Redirect $redirect)
    {
        return view('admin.redirects.edit', compact('redirect'));
    }

    /**
     * Update the specified redirect.
     */
    public function update(Request $request, Redirect $redirect)
    {
        $validated = $request->validate([
            'from_url' => 'required|string|max:2048|unique:redirects,from_url,'.$redirect->id,
            // BUG-014 FIX: Validasi to_url pada update juga (konsisten dengan store)
            'to_url' => ['required', 'string', 'max:2048', function ($attribute, $value, $fail) {
                if (! str_starts_with($value, '/') && ! preg_match('#^https?://#i', $value)) {
                    $fail('URL tujuan harus berupa path relatif (/) atau URL lengkap (https://).');
                }
                if (preg_match('#^(javascript|data|vbscript):#i', $value)) {
                    $fail('URL tujuan mengandung protokol yang tidak diizinkan.');
                }
            }],
            'status_code' => 'required|integer|in:301,302',
        ]);

        $redirect->update($validated);

        return redirect()
            ->route('admin.redirects.index')
            ->with('success', 'Redirect berhasil diperbarui.');
    }

    /**
     * Remove the specified redirect.
     */
    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return redirect()
            ->route('admin.redirects.index')
            ->with('success', 'Redirect berhasil dihapus.');
    }
}
