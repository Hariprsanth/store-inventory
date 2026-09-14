<?php

namespace App\Services;

use App\Exceptions\OutOfStockException;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * @param array{customer_name: string, customer_email: string, items: array<int, array{product_id: int, quantity: int}>} $data
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name']]
            );

            $subtotal = 0;
            $tax_total = 0;
            $lines = [];

            foreach ($data['items'] as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                $quantity = (int) $item['quantity'];
                $deducted = Product::where('id', $product->id)->where('stock', '>=', $quantity)->decrement('stock', $quantity);
                if (! $deducted) {
                    throw new OutOfStockException($product->name, $quantity, $product->stock);
                }

                $unit_price = (float) $product->price;
                $tax_percentage = (float) $product->tax_percentage;
                $line_subtotal = round($unit_price * $quantity, 2);
                $line_tax = round($line_subtotal * ($tax_percentage / 100), 2);
                $line_total = round($line_subtotal + $line_tax, 2);
                $subtotal += $line_subtotal;
                $tax_total += $line_tax;

                $lines[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'tax_percentage' => $tax_percentage,
                    'line_subtotal' => $line_subtotal,
                    'line_tax' => $line_tax,
                    'line_total' => $line_total,
                ];
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($tax_total, 2),
                'grand_total' => round($subtotal + $tax_total, 2),
                'status' => 'confirmed',
            ]);

            foreach ($lines as $line) {
                $order->orderLines()->create($line);
            }

            SendOrderConfirmation::dispatch($order);
            return $order->load('orderLines.product', 'customer');
        });
    }
}