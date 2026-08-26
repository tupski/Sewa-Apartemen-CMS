<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Services\SeoService;
use App\Services\SettingsService;
use Carbon\Carbon;

class PromotionController extends Controller
{
    /**
     * Display the public promotions page.
     *
     * Shows active, currently-valid vouchers that the public can use when
     * booking. A voucher is considered public-facing when it is active, within
     * its valid date window, and has not exhausted its usage limit.
     */
    public function index()
    {
        $today = Carbon::today();

        $vouchers = Voucher::where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->orderByRaw('valid_until IS NULL, valid_until asc')
            ->get()
            // Belt-and-suspenders: re-check with the model's own validity rules.
            ->filter(fn (Voucher $v) => $v->isValid())
            ->values();

        $seo = SeoService::metaTags(
            __('promo.title') . ' - ' . SettingsService::get('site_name', config('app.name')),
            __('promo.subtitle'),
            route('promotions'),
        );

        return view('promotions.index', compact('vouchers', 'seo'));
    }
}
