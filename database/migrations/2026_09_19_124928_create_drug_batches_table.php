<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_batches', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('drug_id')
                ->constrained('drugs')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Batch Identification
            $table->string('batch_number', 100);

            // Batch Information
            $table->date('expiry_date');

            // Pricing
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);

            // Inventory Quantities
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_available')->default(0);

            // Receiving
            $table->timestamp('received_at');

            $table->timestamps();

            // A batch number is unique for each drug.
            $table->unique(
                ['drug_id', 'batch_number'],
                'drug_batches_drug_batch_unique'
            );

            // Indexes
            $table->index('expiry_date');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_batches');
    }
};