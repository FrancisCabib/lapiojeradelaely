<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 32)->unique();
            $table->string('customer_name', 120);
            $table->text('order_summary');
            $table->date('scheduled_date');
            $table->string('scheduled_time', 5);
            $table->string('payment_method', 20);
            $table->unsignedInteger('total_amount')->nullable();
            $table->unsignedInteger('deposit_amount')->nullable();
            $table->unsignedTinyInteger('deposit_percent')->default(50);
            $table->string('status', 30)->default('pending_deposit');
            $table->text('notes')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->json('items')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
