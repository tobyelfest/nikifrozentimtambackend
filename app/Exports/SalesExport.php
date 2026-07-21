<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        return Sale::with('user')
            ->get()
            ->map(function ($sale) {

                return [

                    'invoice' => $sale->invoice_number,

                    'tanggal' => $sale->sale_date,

                    'kasir' => $sale->user->name,

                    'subtotal' => $sale->total,

                    'diskon' => $sale->discount,

                    'pajak' => $sale->tax,

                    'grand_total' => $sale->grand_total,

                    'pembayaran' => $sale->payment_method,

                    'status' => $sale->status,

                ];

            });
    }

    public function headings(): array
    {
        return [

            'Invoice',

            'Tanggal',

            'Kasir',

            'Subtotal',

            'Diskon',

            'Pajak',

            'Grand Total',

            'Metode Pembayaran',

            'Status',

        ];
    }
}