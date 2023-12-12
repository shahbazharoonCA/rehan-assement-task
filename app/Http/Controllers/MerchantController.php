<?php

namespace App\Http\Controllers;

use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Order;

class MerchantController extends Controller
{
    protected $merchantService;

    public function __construct(
        MerchantService $merchantService
    ) {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
{
    $from = $request->input('from');
    $to = $request->input('to');

    // Your logic to calculate order stats
    $count = Order::whereBetween('created_at', [$from, $to])->count();
    $revenue = Order::whereBetween('created_at', [$from, $to])->sum('subtotal');
    
    // Calculate commissions owed for orders with an affiliate within the date range
    $commissionsOwed = Order::whereBetween('created_at', [$from, $to])
        ->sum('commission_owed');

    $noAffiliate = Order::whereBetween('created_at', [$from, $to])
        ->whereNull('affiliate_id') // Consider only orders with an affiliate
        ->sum('commission_owed');

    // Round the calculated values to handle floating-point precision
    $revenue = round($revenue, 2);
    $commissionsOwed = round($commissionsOwed, 2);
    $noAffiliate = round($noAffiliate, 2);

    // Return the rounded values in the JSON response
    return response()->json([
        'count' => $count,
        'revenue' => $revenue,
        'commissions_owed' => number_format($commissionsOwed - $noAffiliate, 14, '.', '')
    ]);
}


}
