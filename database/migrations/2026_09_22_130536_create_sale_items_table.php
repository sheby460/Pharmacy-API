<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('drug_id')
                ->constrained('drugs')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('drug_batch_id')
                ->constrained('drug_batches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Quantity and price snapshot
            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            $table->timestamps();

            $table->index('drug_id');
            $table->index('drug_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};