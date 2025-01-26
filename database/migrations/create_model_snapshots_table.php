<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_snapshots', function (Blueprint $table) {
             $table->id();
             $table->morphs('origin');
             $table->json('storage')->nullable()->default(null);
             $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_snapshots');
    }
};