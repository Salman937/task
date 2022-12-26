<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use App\Services\ApiService;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    protected $apiSvc;
    public function __construct(ApiService $apiService) {
        $this->apiSvc = $apiService;
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method
        $discountCode = $this->apiSvc->createDiscountCode($merchant);

        $affiliateData = [
            'merchant_id' => $merchant->id,
            'user_id' => $merchant->user_id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode['code']
        ];

        $result = Affiliate::create($affiliateData);

        $affiliate = ['name' => $name];

        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $result;
    }
}
