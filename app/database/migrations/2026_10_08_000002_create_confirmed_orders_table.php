<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmed_orders', function (Blueprint $table) {
            $table->id();
            $table->string('client_ref')->unique();
            $table->string('order_id');
            $table->string('location_id')->index();
            $table->string('location_name');
            $table->string('item_id');
            $table->string('item_name');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmed_orders');
    }
};
