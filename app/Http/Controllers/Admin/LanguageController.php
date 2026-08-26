<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

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

    public function toggleStatus(Language $language)
    {
        if ($language->is_default && $language->is_active) {
            return response()->json(['success' => false, 'message' => 'Bahasa default tidak bisa dinonaktifkan.'], 422);
        }
        $language->update(['is_active' => !$language->is_active]);
        return response()->json(['success' => true, 'is_active' => $language->is_active]);
    }
}
