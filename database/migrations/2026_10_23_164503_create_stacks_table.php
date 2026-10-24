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
		Schema::create('stacks', function (Blueprint $table) {
			$table->id();
			$table->foreignId('board_id')->constrained()->cascadeOnDelete();
			$table->string('name');
			// Fractional-index keys must sort byte by byte, not by locale rules.
			$table->string('position')->collation(match (Schema::getConnection()->getDriverName()) {
				'pgsql' => 'C',
				'mysql', 'mariadb' => 'utf8mb4_bin',
				default => null,
			});
			$table->timestamps();

			$table->index(['board_id', 'position']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('stacks');
	}
};
