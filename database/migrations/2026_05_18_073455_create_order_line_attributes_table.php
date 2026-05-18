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
        Schema::create('order_line_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_line_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('key');
            $table->string('label');
            $table->string('value');
            $table->string('raw_value')->nullable();
            $table->timestamps();

            $table->index(['order_line_product_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_line_attributes');
    }
};
