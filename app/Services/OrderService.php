<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Mockery;
use App\Exceptions\AffiliateCreateException;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected AffiliateService $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    public function processOrder(array $data)
    {
        try {
            // Check if the order with the same order_id already exists
        $existingOrder = Order::where('external_order_id', $data['order_id'])->first();

        // If the order already exists, ignore and return
        if ($existingOrder) {
            return;
        }

        $merchant = $this->getMerchantByDomain($data['merchant_domain']);

        // Call findOrCreateAffiliate using the AffiliateService instance
        $affiliate = $this->affiliateService->findOrCreateAffiliate([
            'name' => $data['customer_name'],
            'email' => $data['customer_email'],
            'commission_rate' => round($data['subtotal_price'] * 0.1, 2), // Set as needed
            'discount_code' => $data['discount_code'], // Set as needed
        ], $merchant );
        // Add debug statements
    
        // Create a new order
        Order::create([
            'order_id' => $data['order_id'],
            'merchant_id' => $this->getMerchantIdByDomain($data['merchant_domain']),
            'affiliate_id' => $affiliate->id,
            'subtotal' => $data['subtotal_price'],
            'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
            'external_order_id' => $data['order_id'],
            'discount_code' => $data['discount_code'],
        ]);
        } catch (AffiliateCreateException $exception) {
            Log::error('Affiliate creation failed: ' . $exception->getMessage());
        }
    }

    protected function findOrCreateAffiliate($email, $name, Merchant $merchant)
    {
        // If $email is a string, assume it's the email
        if (is_string($email)) {
            $data = ['email' => $email, 'name' => $name];
        } else {
            $data = $email;
        }

        return $this->affiliateService->findOrCreateAffiliate($data, $merchant);
    }


    protected function getMerchantByDomain(string $merchantDomain): Merchant
    {
        $merchant = Merchant::where('domain', $merchantDomain)->first();

        if (!$merchant) {
            // Handle the case where the merchant is not found
            // You might want to throw an exception or log an error
        }

        return $merchant;
    }

    /**
     * Get merchant ID based on the provided domain.
     *
     * @param string $merchantDomain
     * @return int
     */
    protected function getMerchantIdByDomain(string $merchantDomain): int
    {
        $merchant = Merchant::where('domain', $merchantDomain)->first();

        return $merchant ? $merchant->id : 0;
    }

    /**
     * Log any commissions for the order.
     *
     * @param Order $order
     * @return void
     */
    protected function logCommissions(Order $order)
    {
        // Implement your commission calculation and logging logic here
        // For simplicity, let's assume the commission is 10% of the subtotal price
        $commission = $order->subtotal * 0.10;

        // Update the commission_owed field in the order
        $order->update(['commission_owed' => $commission]);

        // Log the commission (You may want to save it in a commissions table or perform any other action)
        // Example: CommissionLog::create(['order_id' => $order->id, 'amount' => $commission]);

        // For demonstration purposes, let's just echo the commission amount
        echo "Commission for Order {$order->order_id}: $commission\n";
    }
}
