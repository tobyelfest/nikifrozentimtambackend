<?php

namespace App\Http\Controllers;

use App\Models\StoreStock;

class AlertController extends Controller
{
    public function lowStock()
    {
        $stocks = StoreStock::with('product')
            ->get()
            ->filter(function ($stock) {
                return $stock->qty <= $stock->product->minimum_stock;
            })
            ->values();

        return response()->json(
            $stocks->map(function ($stock) {
                return [
                    'product' => $stock->product->name,
                    'stock' => $stock->qty,
                    'minimum_stock' => $stock->product->minimum_stock,
                    'status' => 'LOW STOCK'
                ];
            })
        );
    }
}