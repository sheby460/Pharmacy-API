<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();

            // Drug Identification
            $table->string('drug_code')->unique();
            $table->string('drug_name');
            $table->string('generic_name')->nullable();

            // Classification
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sub_category_id')
                ->constrained('sub_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Drug Details
            $table->string('manufacturer')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('unit')->nullable();
            $table->text('description')->nullable();

            // Barcode
            $table->string('barcode')
                ->nullable()
                ->unique();

            // Inventory Alert
            $table->unsignedInteger('reorder_level')
                ->default(0);

            // Default Selling Price
            $table->decimal('selling_price', 12, 2);

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};