<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;

class ExpiredProductController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $products = Product::with('storeStock')->get();

        $expired = [];
        $warning = [];
        $safe = [];

        foreach ($products as $product) {

            $expiredDate = Carbon::parse($product->expired_date);

            $daysLeft = $today->diffInDays($expiredDate, false);

            $data = [
                'id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'expired_date' => $product->expired_date,
                'days_left' => $daysLeft,
                'stock' => optional($product->storeStock)->qty ?? 0,
            ];

            if ($daysLeft < 0) {

                $expired[] = $data;

            } elseif ($daysLeft <= 30) {

                $warning[] = $data;

            } else {

                $safe[] = $data;

            }
        }

        return response()->json([
            'expired' => $expired,
            'warning' => $warning,
            'safe' => $safe,
        ]);
    }
}