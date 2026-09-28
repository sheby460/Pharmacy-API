
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
        Schema::table('sales', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('status');

            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancellation_reason')
                ->nullable()
                ->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by']);

            $table->dropColumn([
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });
    }
};