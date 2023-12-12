<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        // TODO: Implement payout logic based on your application requirements
        // Example: Use ApiService to send a payout to the affiliate's email with the correct amount

        DB::beginTransaction();

        try {
            $apiService->sendPayout($this->order->affiliate->user->email, $this->order->commission_owed);

            // Mark the order as paid if the payout is successful
            $this->order->update(['payout_status' => Order::STATUS_PAID]);

            DB::commit();
        } catch (\Exception $exception) {
            // Handle the exception (log or perform any other action)
            // For demonstration purposes, let's log the exception
            Log::error('Payout failed: ' . $exception->getMessage());

            DB::rollBack();
            
            // Re-throw the exception to notify the queue about the failure
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
