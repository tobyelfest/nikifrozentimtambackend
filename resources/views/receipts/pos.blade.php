<!DOCTYPE html>
<html>
<head>
    <title>Struk POS</title>
    <style>
        body {
            font-family: monospace;
            width: 300px;
            margin: auto;
        }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; font-size: 12px; }
        .right { text-align: right; }
    </style>
</head>
<body onload="window.print()">

<div class="center">
    <h3>NIKI FROZEN</h3>
    <small>POS SYSTEM</small>
</div>

<div class="line"></div>

<p>
    Invoice: {{ $sale->invoice_number }} <br>
    Tanggal: {{ $sale->sale_date }} <br>
    Kasir: {{ $sale->user->name ?? '-' }}
</p>

<div class="line"></div>

<table>
@foreach($sale->details as $item)
<tr>
    <td colspan="2">{{ $item->product->name }}</td>
</tr>
<tr>
    <td>
        {{ $item->qty }} x {{ number_format($item->price) }}
    </td>
    <td class="right">
        {{ number_format($item->subtotal) }}
    </td>
</tr>
@endforeach
</table>

<div class="line"></div>

<table>
<tr>
    <td>Total</td>
    <td class="right">{{ number_format($sale->total) }}</td>
</tr>
<tr>
    <td>Diskon</td>
    <td class="right">{{ number_format($sale->discount) }}</td>
</tr>
<tr>
    <td>Pajak</td>
    <td class="right">{{ number_format($sale->tax) }}</td>
</tr>
<tr>
    <td><b>Grand Total</b></td>
    <td class="right"><b>{{ number_format($sale->grand_total) }}</b></td>
</tr>
</table>

<div class="line"></div>

<div class="center">
    <p>Terima kasih 🙏</p>
</div>

</body>
</html>