<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_root_models', function (Blueprint $table) {
            $table->id();
            $table->string('attribute1');
            $table->string('attribute2');
            $table->string('attribute3');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_root_models');
    }
};