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
        Schema::create('drugs', function (Blueprint $table) {
            $table->id();
            $table->string('drug_code')->unique();
            $table->string('drug_name');
            $table->string('generic_name')->nullable();
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('unit')->nullable();
            $table->string('barcode')->nullable();

            $table->foreignId('sub_category_id')->constrained()->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('restrict')->onUpdate('cascade');

            $table->unsignedInteger('reorder_level')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('purchasing_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
           
            $table->string('expiry_date');
            $table->boolean('is_active')->default(true);
           
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
