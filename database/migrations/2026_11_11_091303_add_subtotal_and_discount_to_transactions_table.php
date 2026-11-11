<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('invoice_number');
            }
            if (!Schema::hasColumn('transactions', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'discount')) {
                $table->dropColumn('discount');
            }
            if (Schema::hasColumn('transactions', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
        });
    }
};
