<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant|null
     * @throws \Exception
     */
    public function register(array $data): ?Merchant
    {
        try {

            // Find or create a user
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['api_key'],
                    'type' => User::TYPE_MERCHANT,
                ]
            );

            // Check if a merchant already exists for the user
            $existingMerchant = Merchant::where('user_id', $user->id)->first();
            if ($existingMerchant) {
                throw new \Exception('Merchant already exists for the user.');
            }

            $merchant = Merchant::create([
                'user_id' => $user->id,
                'domain' => $data['domain'],
                'display_name' => $data['name'],
            ]);

            // Continue with merchant creation
            return $merchant;

        } catch (QueryException $e) {
            // Log the error or handle it in a way that makes sense for your application
            throw new \Exception('User creation failed. Reason: ' . $e->getMessage());
        }
    }

    /**
     * Update the user.
     *
     * @param User $user
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
{
    // Update user and merchant information
    $result = $user->update([
        'name' => $data['name'],
        'email' => $data['email'],
    ]);

    $merchant = $user->merchant;
    
    $merchant->update([
        'domain' => $data['domain'],
        'display_name' => $data['name'],
    ]);
}

    /**
     * Find a merchant by their email.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        try {
            // Find user by email
            $user = User::where('email', $email)->first();

            if ($user && $user->type === User::TYPE_MERCHANT) {
                return $user->merchant;
            }

            return null;
        } catch (\Exception $e) {
            // Handle or log the exception
            return null;
        }
    }

    /**
     * Pay out all of an affiliate's orders.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        // Find all unpaid orders for the affiliate
        $unpaidOrders = Order::where('affiliate_id', $affiliate->id)
            ->where('payout_status', Order::STATUS_UNPAID)
            ->get();

        // Dispatch a PayoutOrderJob for each unpaid order
        foreach ($unpaidOrders as $order) {
            PayoutOrderJob::dispatch($order);
        }
    }
}
