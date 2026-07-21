<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice</title>

    <style>

        body{
            font-family: DejaVu Sans;
            font-size:14px;
        }

        table{
            width:100%;
            border-collapse: collapse;
        }

        table th,
        table td{
            border:1px solid #000;
            padding:6px;
        }

        h2{
            text-align:center;
        }

    </style>

</head>

<body>

<h2>NIKI FROZEN</h2>

<p>
<b>Invoice :</b> {{ $sale->invoice_number }} <br>

<b>Tanggal :</b> {{ $sale->sale_date }} <br>

<b>Kasir :</b> {{ $sale->user->name }}
</p>

<table>

<thead>

<tr>

<th>Produk</th>

<th>Qty</th>

<th>Harga</th>

<th>Subtotal</th>

</tr>

</thead>

<tbody>

@foreach($sale->details as $detail)

<tr>

<td>{{ $detail->product->name }}</td>

<td>{{ $detail->qty }}</td>

<td>{{ number_format($detail->price) }}</td>

<td>{{ number_format($detail->subtotal) }}</td>

</tr>

@endforeach

</tbody>

</table>

<br>

<h3>

Subtotal :
Rp {{ number_format($sale->total) }}

</h3>

<h3>

Diskon :
Rp {{ number_format($sale->discount) }}

</h3>

<h3>

PPN :
Rp {{ number_format($sale->tax) }}

</h3>

<h2>

TOTAL :
Rp {{ number_format($sale->grand_total) }}

</h2>

</body>

</html>