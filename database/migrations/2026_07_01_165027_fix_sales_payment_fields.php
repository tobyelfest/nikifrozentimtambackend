<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            if (!Schema::hasColumn('sales', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('total');
            }

            if (!Schema::hasColumn('sales', 'tax')) {
                $table->decimal('tax', 10, 2)->default(0)->after('discount');
            }

            if (!Schema::hasColumn('sales', 'grand_total')) {
                $table->decimal('grand_total', 15, 2)->default(0)->after('tax');
            }

            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->enum('payment_method', [
                    'cash',
                    'qris',
                    'transfer'
                ])->default('cash')->after('grand_total');
            }

            if (!Schema::hasColumn('sales', 'status')) {
                $table->enum('status', [
                    'completed',
                    'cancelled'
                ])->default('completed')->after('payment_method');
            }

        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $table->dropColumn([
                'discount',
                'tax',
                'grand_total',
                'payment_method',
                'status'
            ]);

        });
    }
};