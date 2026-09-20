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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete();

            $table->foreignId('drug_id')
                ->constrained('drugs')
                ->restrictOnDelete();

            $table->foreignId('drug_batch_id')
                ->nullable()
                ->constrained('drug_batches')
                ->nullOnDelete();

            $table->string('batch_number', 100);

            $table->date('expiry_date');

            $table->decimal('purchase_price', 15, 2);

            $table->decimal('selling_price', 15, 2);

            $table->unsignedInteger('quantity_received');

            $table->decimal('line_total', 15, 2);

            $table->timestamps();

            $table->index('purchase_id');
            $table->index('drug_id');
            $table->index('drug_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};