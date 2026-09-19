<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('drug_id')
                ->constrained('drugs')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('drug_batch_id')
                ->constrained('drug_batches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Movement Details
            $table->string('movement_type', 50);

            $table->unsignedInteger('quantity');

            // Stock Snapshot
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');

            // Reference
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Additional Information
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(
                ['drug_batch_id', 'created_at'],
                'stock_movements_batch_created_index'
            );

            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};