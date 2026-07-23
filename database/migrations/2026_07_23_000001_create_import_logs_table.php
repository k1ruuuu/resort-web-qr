<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('filename', 255);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('total_rows')->default(0);
            $table->integer('imported')->default(0);
            $table->integer('skipped')->default(0);
            $table->integer('failed')->default(0);
            $table->json('errors')->nullable();
            $table->string('status', 16)->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
