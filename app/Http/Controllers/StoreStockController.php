<?php

namespace App\Http\Controllers;

use App\Models\StoreStock;
use Illuminate\Http\Request;

class StoreStockController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = StoreStock::with('product', 'branch');

        // Kasir hanya melihat stok cabangnya sendiri
        if ($user->role !== 'admin') {
            $query->where('branch_id', $user->branch_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:0',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Kasir otomatis menggunakan cabangnya sendiri
        if ($user->role !== 'admin') {
            $validated['branch_id'] = $user->branch_id;
        }

        // Admin harus menentukan cabang
        if ($user->role === 'admin' && empty($validated['branch_id'])) {
            return response()->json([
                'message' => 'branch_id wajib diisi oleh admin'
            ], 422);
        }

        $storeStock = StoreStock::create($validated);

        return response()->json(
            $storeStock->load('product', 'branch'),
            201
        );
    }

    public function show(StoreStock $storeStock)
    {
        $user = auth()->user();

        if (
            $user->role !== 'admin' &&
            $storeStock->branch_id !== $user->branch_id
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke stok cabang ini'
            ], 403);
        }

        return response()->json(
            $storeStock->load('product', 'branch')
        );
    }

    public function update(Request $request, StoreStock $storeStock)
    {
        $user = auth()->user();

        if (
            $user->role !== 'admin' &&
            $storeStock->branch_id !== $user->branch_id
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke stok cabang ini'
            ], 403);
        }

        $validated = $request->validate([
            'qty' => 'sometimes|integer|min:0',
        ]);

        $storeStock->update($validated);

        return response()->json(
            $storeStock->load('product', 'branch')
        );
    }

    public function destroy(StoreStock $storeStock)
    {
        $user = auth()->user();

        if (
            $user->role !== 'admin' &&
            $storeStock->branch_id !== $user->branch_id
        ) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke stok cabang ini'
            ], 403);
        }

        $storeStock->delete();

        return response()->json([
            'message' => 'Store stock deleted'
        ]);
    }
}