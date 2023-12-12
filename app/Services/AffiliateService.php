<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {

        // Check if the email is already associated with a merchant
        if (Merchant::where('user_id', User::where('email', $email)->value('id'))->exists()) {
            throw new AffiliateCreateException("Email is already associated with a merchant.");
        }

        // Check if the email is already associated with a merchant
        if (Affiliate::where('user_id', User::where('email', $email)->value('id'))->exists()) {
            throw new AffiliateCreateException('Affiliate with the same email already exists.');
        }

        // Create a new affiliate
        $affiliate = Affiliate::create([
            'user_id' => $merchant->user_id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $this->generateDiscountCode($merchant),
        ]);

        // Find or create a user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => rand(),
                'type' => User::TYPE_MERCHANT,
            ]
        );

        // Send an email notification to the affiliate
        $this->sendAffiliateCreatedEmail($affiliate, $name, $email);

        return $affiliate;
    }

    /**
     * Generate a discount code for the affiliate using the ApiService.
     *
     * @param Merchant $merchant
     * @return string
     */
    protected function generateDiscountCode(Merchant $merchant): string
    {
        $discountCode = $this->apiService->createDiscountCode($merchant);

        return $discountCode['code'];
    }

    /**
     * Send an email notification to the newly created affiliate.
     *
     * @param Affiliate $affiliate
     * @param string $name
     * @param string $email
     * @return void
     */
    protected function sendAffiliateCreatedEmail(Affiliate $affiliate, string $name, string $email)
{
    // You can customize the email content and subject as needed
    $emailData = [
        'affiliate' => $affiliate,
        'name' => $name,
        'discount_code' => $affiliate->discount_code,
        'commission_rate' => round($affiliate->commission_rate, 2), // Round the commission rate
        // Add other data as needed
    ];
    
    Mail::to($email)->send(new AffiliateCreated($affiliate));
}


    /**
     * Find or create an affiliate based on the provided data.
     *
     * @param array|string $data
     * @param Merchant $merchant
     * @return Affiliate
     */


    public function findOrCreateAffiliate($data, Merchant $merchant): Affiliate
    {
        if (is_string($data)) {
            $data = ['email' => $data];
        }

        // Attempt to find an existing affiliate by email
        $affiliate = Affiliate::where('merchant_id', $merchant->id)->first();
        
        if (!$affiliate) {
            // If no existing affiliate is found, create a new one
            $affiliate = DB::transaction(function () use ($data, $merchant) {
                // Create a new user
                $user = User::create([
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'],
                    'type' => User::TYPE_AFFILIATE,
                ]);

                // Create a new affiliate
                return Affiliate::create([
                    'user_id' => $user->id,
                    'merchant_id' => $merchant->id,
                    'commission_rate' => $data['commission_rate'],
                    'discount_code' => $data['discount_code'],
                ]);
            });
        }

        return $affiliate;
    }
    
    /**
     * Create a new affiliate with the provided email and name.
     *
     * @param string $email
     * @param string $name
     * @return Affiliate
     */
    protected function createAffiliate(string $email, string $name): Affiliate
    {
        // Create a new user for the affiliate
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'type' => User::TYPE_AFFILIATE,
            // You may need to set other user attributes as needed
        ]);

        // Create a new affiliate for the user
        return Affiliate::create([
            'user_id' => $user->id,
            'email' => $email,
            // Set other affiliate attributes as needed
        ]);
    }
}
