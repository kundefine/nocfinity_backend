<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    // Storefront: Apply coupon code to subtotal
    public function apply(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($validated['code'])))->first();

        if (!$coupon) {
            return $this->error('Invalid coupon code.', 404);
        }

        $check = $coupon->isValidForAmount((float) $validated['subtotal']);
        if (!$check['valid']) {
            return $this->error($check['message'], 422);
        }

        $discount = $coupon->calculateDiscount((float) $validated['subtotal']);

        return $this->success([
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'discount_amount' => $discount,
            'new_total' => max(0, (float) $validated['subtotal'] - $discount),
        ], 'Coupon applied successfully.');
    }

    // Admin: List all coupons
    public function index(Request $request)
    {
        $coupons = Coupon::latest()->paginate($request->get('per_page', 20));
        return $this->paginated($coupons);
    }

    // Admin: Store coupon
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $coupon = Coupon::create($validated);

        return $this->success($coupon, 'Coupon created successfully.', 201);
    }

    // Admin: Update coupon
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', "unique:coupons,code,{$id}"],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $coupon->update($validated);

        return $this->success($coupon, 'Coupon updated successfully.');
    }

    // Admin: Delete coupon
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return $this->success(null, 'Coupon deleted successfully.');
    }
}
