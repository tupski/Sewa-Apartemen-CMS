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
                $q->where('from_url', 'like', '%' . $request->search . '%')
                  ->orWhere('to_url', 'like', '%' . $request->search . '%');
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_url' => 'required|string|max:2048|unique:redirects,from_url',
            'to_url' => 'required|string|max:2048',
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
            'from_url' => 'required|string|max:2048|unique:redirects,from_url,' . $redirect->id,
            'to_url' => 'required|string|max:2048',
            'status_code' => 'required|integer|in:301,302',
        ]);

        $redirect->update($validated);

        return redirect()
            ->route('admin.redirects.index')
            ->with('success', 'Redirect updated successfully.');
    }

    /**
     * Remove the specified redirect.
     */
    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return redirect()
            ->route('admin.redirects.index')
            ->with('success', 'Redirect deleted successfully.');
    }
}
