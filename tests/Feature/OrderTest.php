<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_calculates_totals_deducts_stock_and_dispatches_confirmation(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'tax_percentage' => 18,
            'stock' => 5,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.tax_total', 36)
            ->assertJsonPath('data.grand_total', 236);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'grand_total' => 236,
        ]);
        Queue::assertPushed(SendOrderConfirmation::class);
    }

    public function test_order_fails_without_changing_stock_when_requested_quantity_is_unavailable(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', "Insufficient stock for '{$product->name}': requested 2, only 1 available.");

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1,
        ]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_only_one_order_can_consume_the_last_unit(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);
        $payload = [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $firstResponse = $this->postJson('/api/orders', $payload);
        $secondResponse = $this->postJson('/api/orders', $payload);

        $firstResponse->assertCreated();
        $secondResponse->assertUnprocessable();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 0,
        ]);
    }
}