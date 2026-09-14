<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class OutOfStockException extends Exception
{
    public function __construct(string $productName, int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock for '{$productName}': requested {$requested}, only {$available} available."
        );
    }

    public function render(Request $request)
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}