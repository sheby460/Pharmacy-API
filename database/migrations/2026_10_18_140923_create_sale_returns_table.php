
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('processed_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('status', 30)->default('COMPLETED');
            $table->string('refund_method', 30)->nullable();
            $table->string('refund_status', 30)->default('PENDING');

            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->index(['sale_id', 'created_at']);
            $table->index(['refund_status', 'created_at']);
            $table->index('processed_by');
        });

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

            $table->unsignedInteger('quantity');

            // Historical financial values for this return line.
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);

            // RESTOCK or QUARANTINE.
            $table->string('disposition', 20)->default('QUARANTINE');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('sale_item_id');
            $table->index('drug_batch_id');
            $table->index(['sale_return_id', 'sale_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
