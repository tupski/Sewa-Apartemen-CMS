<?php

namespace App\Http\Controllers;

use App\Http\Requests\NavigationRequest;
use App\Models\Navigation;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the navigations grouped by menu location.
     */
    public function index()
    {
        $navigations = Navigation::with(['page', 'children'])
            ->rootItems()
            ->ordered()
            ->paginate(20);

        $groupedNavigations = Navigation::with(['page', 'children'])
            ->rootItems()
            ->ordered()
            ->get()
            ->groupBy('menu_location');

        return view('admin.navigations.index', compact('navigations', 'groupedNavigations'));
    }

    /**
     * Show the form for creating a new navigation.
     */
    public function create()
    {
        $navigations = Navigation::rootItems()
            ->ordered()
            ->get();

        return view('admin.navigations.create', compact('navigations'));
    }

    /**
     * Store a newly created navigation in storage.
     */
    public function store(NavigationRequest $request)
    {
        $navigation = Navigation::create($request->validated());

        return redirect()
            ->route('admin.navigations.index')
            ->with('success', 'Navigation item created successfully.');
    }

    /**
     * Show the form for editing the specified navigation.
     */
    public function edit(Navigation $navigation)
    {
        $navigation->load(['page', 'children']);

        $navigations = Navigation::rootItems()
            ->where('id', '!=', $navigation->id)
            ->ordered()
            ->get();

        return view('admin.navigations.edit', compact('navigation', 'navigations'));
    }

    /**
     * Update the specified navigation in storage.
     */
    public function update(NavigationRequest $request, Navigation $navigation)
    {
        $navigation->update($request->validated());

        return redirect()
            ->route('admin.navigations.index')
            ->with('success', 'Navigation item updated successfully.');
    }

    /**
     * Remove the specified navigation from storage.
     */
    public function destroy(Navigation $navigation)
    {
        $navigation->delete();

        return redirect()
            ->route('admin.navigations.index')
            ->with('success', 'Navigation item deleted successfully.');
    }

    /**
     * Update the order of multiple navigation items.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'required|integer|exists:navigations,id',
        ]);

        $items = $request->input('items');

        foreach ($items as $order => $id) {
            Navigation::where('id', $id)->update(['order' => $order]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Navigation items reordered successfully.',
        ]);
    }

    /**
     * Update the status of the specified navigation.
     */
    public function updateStatus(Navigation $navigation, Request $request)
    {
        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $navigation->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Navigation status updated successfully.',
        ]);
    }
}
