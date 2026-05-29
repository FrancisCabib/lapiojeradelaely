<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_sections', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekday')->nullable()->after('sort_order');
            $table->unique('weekday');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_sections', function (Blueprint $table) {
            $table->dropUnique(['weekday']);
            $table->dropColumn('weekday');
        });
    }
};
