<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Dvarilek\LaravelSnap\Tests\Models\TestParent1Model;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_another_parent_models_1', function (Blueprint $table) {
            $table->id();
            $table->string('attribute1')->nullable()->default(null);
            $table->string('attribute2')->nullable()->default(null);
            $table->string('attribute3')->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_another_parent_models_1');
    }
};