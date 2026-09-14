<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OutOfStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Models\Customer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return (new OrderResource($order))->response()->setStatusCode(201);
        } catch (OutOfStockException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function orderhistory(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $customer = Customer::where('email', $request->query('email'))->first();
        if (! $customer) {
            return response()->json([
                'message' => 'No customer found with this email.',
                'data' => [],
            ], 404);
        }

        $orders = $customer->orders()
            ->with('orderLines.product')
            ->latest()
            ->get();
        return response()->json([
            'data' => $orders,
        ]);
    }
}