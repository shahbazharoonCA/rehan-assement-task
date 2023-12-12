<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount)
    {
        // Simulate sending a payout by logging a message
        Log::info("Payout of $amount sent to $email");
        
        // In a real-world scenario, you would interact with a payment gateway or other service here
    }
}
