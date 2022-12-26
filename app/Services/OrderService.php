<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use DB;

class OrderService
{
    protected $affiliateSvc;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateSvc = $affiliateService;
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // TODO: Complete this method
        $affiliateResult = $this->checkAffiliate($data['customer_email']);

        $orderResult = $this->checkDuplicateOrder($data['order_id']);

        if ($orderResult == true) {
            return ['message' => 'Order Already Exists'];
        }

        $orderData = [
            'merchant_id' => $affiliateResult->merchant_id,
            'affiliate_id' => $affiliateResult->id,
            'subtotal' => $data['subtotal_price'],
            'commission_owed' => $data['subtotal_price'] * $affiliateResult->commission_rate,
            'discount_code' => $data['discount_code'],
        ];;

        $order = Order::create($orderData);

        return ['message' => 'Order Process Successfully'];
    }

    /**
     * Check Duplicate Order.
     *
     * @param {order_id: int}
     * @return boolean
     */
    private function checkDuplicateOrder($order_id)
    {
        $checkOrder = Order::where('id', $order_id)->first();

        if ($checkOrder === null) {
            return false;
        }
        return true;
    }

    /**
     * check affiliate customer_email is not already associated with one.
     *
     * @param {email: string}
     * @return affiliate
     */
    private function checkAffiliate($email)
    {
        $checkAffiliate = DB::table('users')
            ->select('merchants.id as merchant_id', 'merchants.user_id', 'users.email', 'user.name', 'affiliates.id', 'affiliates.commission_rate')
            ->join('merchants', 'merchants.user_id', 'users.id')
            ->join('affiliates', 'affiliates.merchant_id', 'merchants.id')
            ->where('users.email', $email)
            ->first();

        if ($checkAffiliate === null) {

            $merchant = DB::table('users')
                ->select('merchants.id', 'merchants.user_id', 'users.email', 'user.name')
                ->join('merchants', 'merchants.user_id', 'users.id')
                ->where('users.email', $email)
                ->first();

            $resultAffiliate = $this->affiliateSvc->register($merchant, $merchant->email, $merchant->name, 0.1);

            return $resultAffiliate;
        }

        return $checkAffiliate;
    }
}
