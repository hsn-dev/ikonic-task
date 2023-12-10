<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

    class OrderService
    {
        public function __construct(
            protected AffiliateService $affiliateService
        ) {}

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
            $existingOrder = Order::where('external_order_id', $data['order_id'])->first();
            if ($existingOrder)
                return;

            $affiliate = Affiliate::firstOrCreate(
                ['discount_code' => $data['discount_code']],
                ['merchant_id' => Merchant::where('domain', $data['merchant_domain'])->value('id')]
            );

            $this->affiliateService->register(
                $affiliate->merchant,
                $data['customer_email'],
                $data['customer_name'],
                $affiliate->commission_rate
            );

            $commissionOwed = $data['subtotal_price'] * $affiliate->commission_rate;

            Order::create([
                'external_order_id' => $data['order_id'],
                'subtotal' => $data['subtotal_price'],
                'affiliate_id' => $affiliate->id,
                'merchant_id' => $affiliate->merchant_id,
                'commission_owed' => $commissionOwed,
            ]);
        }
    }
