<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Dvarilek\LaravelSnap\Tests\Models\{TestParent1Model, TestAnotherParent1Model};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_versionable_models', function (Blueprint $table) {
            $table->id();
            $table->string('attribute1')->nullable()->default(null);
            $table->string('attribute2')->nullable()->default(null);
            $table->string('attribute3')->nullable()->default(null);
            $table->string('castable1')->nullable()->default(null);
            $table->string('extraAttribute1')->nullable()->default(null);
            $table->string('extraAttribute2')->nullable()->default(null);
            $table->foreignIdFor(TestParent1Model::class, 'parent_model_id')->nullable()->default(null);
            $table->foreignIdFor(TestAnotherParent1Model::class, 'another_parent_model_id')->nullable()->default(null);
            $table->string('hidden1')->nullable()->default(null);
            $table->unsignedBigInteger('current_version')->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_versionable_models');
    }
};