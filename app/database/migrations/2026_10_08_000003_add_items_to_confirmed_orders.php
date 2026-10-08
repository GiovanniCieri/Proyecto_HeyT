<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confirmed_orders', function (Blueprint $table) {
            $table->text('items_json')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('confirmed_orders', function (Blueprint $table) {
            $table->dropColumn('items_json');
        });
    }
};
