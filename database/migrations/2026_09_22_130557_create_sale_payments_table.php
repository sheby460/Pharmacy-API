<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Payment information
            $table->string('payment_method', 30);
            $table->decimal('amount', 12, 2);

            // Useful for mobile money or card transactions
            $table->string('reference')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('payment_method');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};