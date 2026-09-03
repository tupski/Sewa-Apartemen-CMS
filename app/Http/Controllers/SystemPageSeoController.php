<?php

namespace App\Http\Controllers;

use App\Http\Requests\SystemPageSeoRequest;
use App\Models\SystemPage;

/**
 * Admin editor for the SEO metadata of non-CMS routes (homepage, apartment
 * listing, apartment detail template, blog index, promo, contact).
 *
 * These routes have no Eloquent record of their own, so their metadata is stored
 * on the shared polymorphic `seo_metadata` table through a lightweight
 * {@see SystemPage} registry row. Editing mirrors the Page/Post/Property flow:
 * nested `seo[...]` inputs persisted via `updateOrCreate` on the morph.
 */
class SystemPageSeoController extends Controller
{
    /**
     * Show the SEO editor for one registry route.
     */
    public function edit(SystemPage $systemPage)
    {
        $systemPage->load('seo');

        return view('admin.pages.system-seo', compact('systemPage'));
    }

    /**
     * Persist the SEO metadata for one registry route.
     */
    public function update(SystemPageSeoRequest $request, SystemPage $systemPage)
    {
        try {
            $systemPage->seo()->updateOrCreate([], [
                'meta_title' => $request->input('seo.meta_title'),
                'meta_description' => $request->input('seo.meta_description'),
                'open_graph' => $this->cleanArray($request->input('seo.open_graph')),
                'twitter' => $this->cleanArray($request->input('seo.twitter')),
                'canonical_url' => $request->input('seo.canonical_url'),
                'index_status' => $request->boolean('seo.index_status', true),
            ]);

            return redirect()
                ->route('admin.pages.index')
                ->with('success', 'SEO untuk "'.$systemPage->label.'" berhasil disimpan.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan SEO: '.$e->getMessage());
        }
    }

    /**
     * Drop empty sub-keys so an untouched Open Graph / Twitter group is stored as
     * null instead of an array of empty strings (which would otherwise override
     * the computed defaults with blanks).
     *
     * @param  mixed  $value
     * @return array<string, string>|null
     */
    protected function cleanArray($value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $filtered = array_filter(
            $value,
            fn ($item) => is_string($item) ? trim($item) !== '' : $item !== null
        );

        return $filtered === [] ? null : $filtered;
    }
}
