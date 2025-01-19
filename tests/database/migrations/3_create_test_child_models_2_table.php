<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Dvarilek\LaravelSnapshotTree\Tests\Models\TestChildModel;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_child_models_2', function (Blueprint $table) {
            $table->id();
            $table->string('attribute1');
            $table->string('attribute2');
            $table->string('attribute3');
            $table->foreignIdFor(TestChildModel::class, 'parent_model_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_child_models_2');
    }
};