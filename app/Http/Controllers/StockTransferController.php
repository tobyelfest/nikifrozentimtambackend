<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Models\StoreStock;
use App\Models\StockTransferHistory;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {

            $warehouse = WarehouseStock::where(
                'product_id',
                $validated['product_id']
            )->first();

            if (!$warehouse) {
                return response()->json([
                    'message' => 'Stok gudang tidak ditemukan'
                ], 404);
            }

            if ($warehouse->qty < $validated['qty']) {
                return response()->json([
                    'message' => 'Stok gudang tidak mencukupi'
                ], 400);
            }

            $warehouse->qty -= $validated['qty'];
            $warehouse->save();

            $store = StoreStock::firstOrCreate(
                ['product_id' => $validated['product_id']],
                ['qty' => 0]
            );

            $store->qty += $validated['qty'];
            $store->save();

            // Simpan histori transfer
            StockTransferHistory::create([
                'product_id' => $validated['product_id'],
                'user_id' => auth()->id(),
                'qty' => $validated['qty'],
                'from_location' => 'warehouse',
                'to_location' => 'store',
                'transfer_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfer berhasil',
                'warehouse_stock' => $warehouse->qty,
                'store_stock' => $store->qty
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function history()
    {
        return response()->json(
            StockTransferHistory::with('product', 'user')
                ->latest('transfer_date')
                ->get()
        );
    }
}