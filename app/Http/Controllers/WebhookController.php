<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Validator;

class WebhookController extends Controller
{
    use RefreshDatabase, WithFaker;
    protected $orderSvc;

    public function __construct(OrderService $orderService){
        $this->orderSvc = $orderService;
    }

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // TODO: Complete this method
        //validate
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'subtotal_price' => 'required',
            'merchant_domain' => 'required',
            'discount_code' => 'required',
            'customer_email' => 'required',
            'customer_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Please Review All Fields',
                'errors' => $validator->errors(),
            ]);
        }

        $this->orderSvc->processOrder($request->all());

        return response()->json([
            'message' => 'Order Process Successfully'
        ]);
    }
}
