<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Dvarilek\LaravelSnapshotTree\Tests\Models\TestRootModel;
use Illuminate\Database\Schema\Blueprint;
use Dvarilek\LaravelSnapshotTree\Tests\Models\TestParent2Model;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_parent_models_1', function (Blueprint $table) {
            $table->id();
            $table->string('attribute1')->nullable()->default(null);
            $table->string('attribute2')->nullable()->default(null);
            $table->string('attribute3')->nullable()->default(null);
            $table->foreignIdFor(TestParent2Model::class, 'parent_model_id')->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_parent_models_1');
    }
};