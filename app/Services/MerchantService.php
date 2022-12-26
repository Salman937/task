<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use DB;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        // TODO: Complete this method

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT
        ];

        $user = User::create($userData);

        $merchantData = [
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ];

        $merchant = Merchant::create($merchantData);

        return [
            'merchant' => $merchant,
            'user' => $user,
        ];
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        // TODO: Complete this method

        $merchantData = [
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ];

        $userData = [
            'email' => $data['email'],
            'password' => $data['api_key'],
        ];

        $user = User::where('id', $user->id)->update($userData);
        $merchant = Merchant::where('user_id', $user->id)->update($merchantData);

        return [
            'message' => 'Merchant Updated Successfully'
        ];
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        // TODO: Complete this method
        $merchant = DB::table('users')
            ->select('users.id', 'users.name', 'users.email', 'users.type', 'merchants.domain', 'merchants.display_name', 'merchants.turn_customers_into_affiliates', 'merchants.default_commission_rate')
            ->join('merchants', 'merchants.user_id', '=', 'users.id')
            ->where('users.email', $email)
            ->first();

        if ($merchant) {
            return $merchant;
        }
        return null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        // TODO: Complete this method
        $orders = DB::table('affiliates')
            ->select('orders.id', 'orders.commission_owed', 'users.email')
            ->join('orders', 'orders.affiliate_id', '=', 'affiliates.id')
            ->join('users', 'users.id', '=', 'affiliates.user_id')
            ->where([
                ['orders.payout_status', 'unpaid'],
                ['orders.affiliate_id', $affiliate->id]
            ])
            ->get();

        Order::where([
            ['orders.payout_status', 'unpaid'],
            ['orders.affiliate_id', $affiliate->id]
        ])->update([
            'payout_status' => Order::STATUS_PAID
        ]);

        foreach ($orders as $key => $order) {
            new PayoutOrderJob($order);
        }

        return ['message' => 'Payout Completed'];
    }
}
