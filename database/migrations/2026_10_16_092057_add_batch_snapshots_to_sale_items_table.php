<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('drug_name_snapshot', 255)->nullable();
            $table->string('drug_code_snapshot', 100)->nullable();
            $table->string('batch_number_snapshot', 100)->nullable();
            $table->date('expiry_date_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'drug_name_snapshot',
                'drug_code_snapshot',
                'batch_number_snapshot',
                'expiry_date_snapshot',
            ]);
        });
    }
};
