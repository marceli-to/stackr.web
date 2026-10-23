<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stack_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Fractional-index keys must sort byte by byte, not by locale rules.
            $table->string('position')->collation(match (Schema::getConnection()->getDriverName()) {
                'pgsql' => 'C',
                'mysql', 'mariadb' => 'utf8mb4_bin',
                default => null,
            });
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->index(['stack_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
