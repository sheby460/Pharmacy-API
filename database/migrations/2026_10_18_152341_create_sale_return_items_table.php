<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_return_id')
                ->constrained('sale_returns')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sale_item_id')
                ->constrained('sale_items')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('drug_id')
                ->constrained('drugs')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('drug_batch_id')
                ->constrained('drug_batches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Quantity returned from the original sale item.
            $table->unsignedInteger('quantity');

            // Historical financial values recorded at return time.
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);

            // RESTOCK or QUARANTINE.
            $table->string('disposition', 20)->default('QUARANTINE');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for return history and reporting.
            $table->index('sale_item_id');
            $table->index('drug_batch_id');
            $table->index([
                'sale_return_id',
                'sale_item_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
