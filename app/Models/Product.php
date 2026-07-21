<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'barcode',
        'name',
        'purchase_price',
        'selling_price',
        'minimum_stock',
        'expired_date'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouseStock()
    {
        return $this->hasOne(WarehouseStock::class);
    }

    public function storeStocks()
    {
        return $this->hasMany(StoreStock::class);
    }

    public function transferHistories()
    {
        return $this->hasMany(StockTransferHistory::class);
    }
}