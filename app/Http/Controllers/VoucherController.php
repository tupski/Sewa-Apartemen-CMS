<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers.
     */
    public function index(Request $request): View
    {
        $query = Voucher::withTrashed();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $vouchers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new voucher.
     */
    public function create(): View
    {
        return view('admin.vouchers.create');
    }

    /**
     * Store a newly created voucher.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validate($request);

        Voucher::create($data);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified voucher.
     */
    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    /**
     * Update the specified voucher.
     */
    public function update(Request $request, Voucher $voucher): RedirectResponse
    {
        $data = $this->validate($request, $voucher);

        $voucher->update($data);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil diperbarui.');
    }

    /**
     * Remove the specified voucher (soft delete).
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus.');
    }

    /**
     * Validate and return voucher request data.
     */
    protected function validate(Request $request, ?Voucher $voucher = null): array
    {
        $codeUnique = 'unique:vouchers,code';
        if ($voucher) {
            $codeUnique .= ',' . $voucher->id;
        }

        $data = $request->validate([
            'code'           => ['required', 'string', 'max:50', $codeUnique],
            'name'           => ['required', 'string', 'max:255'],
            'discount_type'  => ['required', 'string', 'in:percent,fixed'],
            // BUG-023 FIX: discount_value minimal 0.01 (tidak boleh nol).
            // Untuk tipe percent, tambahan validasi max:100 via after-validation di bawah.
            'discount_value'      => ['required', 'numeric', 'min:0.01'],
            'min_booking_amount'  => ['nullable', 'integer', 'min:0'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'usage_limit'         => ['nullable', 'integer', 'min:1'],
            'valid_from'          => ['nullable', 'date'],
            'valid_until'         => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active'           => ['boolean'],
        ]);

        // BUG-023 FIX: Untuk tipe percent, nilai maksimal 100%
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount_value' => 'Diskon persen tidak boleh melebihi 100%.',
            ]);
        }

        // Normalize code to uppercase
        $data['code'] = strtoupper($data['code']);

        // Cast is_active from checkbox (absent = 0)
        $data['is_active'] = $request->boolean('is_active');

        // percent type doesn't need min_booking_amount enforcement here, just pass through
        return $data;
    }
}
