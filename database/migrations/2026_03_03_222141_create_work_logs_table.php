<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('hours', 8, 2);
            $table->unsignedInteger('rate')->comment('In cents');
            $table->date('worked_at');
            $table->string('status')->default('unbilled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
