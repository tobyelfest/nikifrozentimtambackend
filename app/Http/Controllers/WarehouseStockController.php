<?php

namespace App\Http\Controllers;

use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class WarehouseStockController extends Controller
{
    public function index()
    {
        return response()->json(
            WarehouseStock::with('product')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:0'
        ]);

        $warehouseStock = WarehouseStock::create($validated);

        return response()->json($warehouseStock, 201);
    }

    public function show(WarehouseStock $warehouseStock)
    {
        return response()->json(
            $warehouseStock->load('product')
        );
    }

    public function update(Request $request, WarehouseStock $warehouseStock)
    {
        $warehouseStock->update($request->all());

        return response()->json($warehouseStock);
    }

    public function destroy(WarehouseStock $warehouseStock)
    {
        $warehouseStock->delete();

        return response()->json([
            'message' => 'Stock deleted'
        ]);
    }
}