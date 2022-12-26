<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use DB,Validator;

class MerchantController extends Controller
{
    public function __construct(MerchantService $merchantService) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
    */
    public function orderStats(Request $request): JsonResponse
    {
        // TODO: Complete this method

        //validate
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }

        $from = $request->from;
        $to = $request->to;

        $between = DB::table('affiliates')
                    ->select('orders.id','orders.subtotal','orders.commission_owed')
                    ->join('orders','orders.affiliate_id','=','affiliates.id')
                    ->whereBetween('orders.created_at', [$from, $to])
                    ->where('orders.payout_status', Order::STATUS_UNPAID)
                    ->get();

        return response()->json([
            'total_orders' => $between->count(),
            'revenue' => $between->sum('subtotal'),
            'commission_owed' => $between->sum('commission_owed'),
        ]);
    }
}
