<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StoreStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    public function index()
    {
        return response()->json(
            Sale::with('details.product', 'user')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,qris,transfer',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $invoice = 'INV-' . now()->format('YmdHis') . '-' . random_int(100, 999);

            $total = 0;

            // 1. HITUNG TOTAL + CEK STOK
            foreach ($request->items as $item) {

                $product = Product::findOrFail($item['product_id']);

                $stock = StoreStock::where('branch_id', auth()->user()->branch_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$stock || $stock->qty < $item['qty']) {
                    throw new \Exception('Stok tidak cukup untuk ' . $product->name);
                }

                $total += $product->selling_price * $item['qty'];
            }

            $discount = min($request->discount ?? 0, $total);

            $taxBase = max($total - $discount, 0);
            $tax = $taxBase * 0.11;
            $grandTotal = $taxBase + $tax;

            // 2. SIMPAN SALES
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,

                'invoice_number' => $invoice,
                'total' => $total,
                'discount' => $discount,

                'tax' => $tax,

                'grand_total' => $grandTotal,

                'payment_method' => $request->payment_method,

                'payment_status' => 'paid',

                'sale_date' => now(),
            ]);

            // 3. SIMPAN DETAIL + KURANGI STOK
            foreach ($request->items as $item) {

                $product = Product::findOrFail($item['product_id']);

                $qty = $item['qty'];
                $price = $product->selling_price;

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $price * $qty
                ]);

                $stock = StoreStock::where('branch_id', auth()->user()->branch_id)
                    ->where('product_id', $product->id)
                    ->first();
                $stock->qty -= $qty;
                $stock->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Checkout berhasil',

                'invoice' => $invoice,

                'total' => $total,

                'discount' => $discount,

                'tax' => $tax,


                'grand_total' => $grandTotal
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

                return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function show(Sale $sale)
    {
        return response()->json(
            $sale->load('details.product', 'user')
        );
    }

    public function report(Request $request)
    {
        $start = $request->start
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfMonth();

        $end = $request->end
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfMonth();

        $sales = Sale::with('user', 'details.product', 'branch')
            ->where('branch_id', auth()->user()->branch_id)
            ->whereBetween('sale_date', [$start, $end])
            ->latest()
            ->get();

        return response()->json([
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'total_transactions' => $sales->count(),
            'total_income' => $sales->sum('total'),
            'sales' => $sales,
        ]);
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();

        try {

            $sale->load('details');

            foreach ($sale->details as $detail) {

                $stock = StoreStock::where(
                    'product_id',
                    $detail->product_id
                )->first();

                if ($stock) {
                    $stock->qty += $detail->qty;
                    $stock->save();
                }
            }

            $sale->details()->delete();

            $sale->delete();

            DB::commit();

            return response()->json([
                'message' => 'Penjualan berhasil dibatalkan'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel()
    {
        return Excel::download(
            new SalesExport,
            'laporan_penjualan.xlsx'
        );
    }

    public function invoice(Sale $sale)
    {
        $sale->load(
            'user',
            'details.product'
        );

        $pdf = Pdf::loadView(
            'invoices.invoice',
            compact('sale')
        );

        return $pdf->download(
            'Invoice-'.$sale->invoice_number.'.pdf'
        );
    }

    public function downloadinvoice($id)
    {
        $sale = Sale::with('details.product')->find($id);

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found'
            ], 404);
        }

        return response()->json([
            'id' => $sale->id,
            'sale_date' => $sale->sale_date,
            'total' => $sale->total,
            'discount' => $sale->discount,
            'tax' => $sale->tax,
            'grand_total' => $sale->grand_total,
            'payment_method' => $sale->payment_method,
            'payment_status' => $sale->payment_status,
            'items' => $sale->details
        ]);
    }

    public function receipt(Sale $sale)
    {
        $sale->load('details.product', 'user');

        return view('receipts.pos', compact('sale'));
    }
}