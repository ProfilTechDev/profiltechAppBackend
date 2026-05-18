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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wc_order_id')->unique();
            $table->string('wc_number')->nullable();
            $table->string('status', 32)->index();
            $table->string('currency', 3);
            $table->decimal('total', 12, 2);
            $table->string('payment_method_title')->nullable();
            $table->text('customer_note')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_paid')->nullable();
            $table->timestamp('wc_modified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
