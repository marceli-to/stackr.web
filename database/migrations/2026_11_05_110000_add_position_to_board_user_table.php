<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Each member's own order of boards on the overview. Null until they
	 * move a board; unplaced boards follow the placed ones by name.
	 */
	public function up(): void
	{
		Schema::table('board_user', function (Blueprint $table) {
			// Fractional-index keys must sort byte by byte, not by locale rules.
			$table->string('position')->nullable()->after('role')->collation(match (Schema::getConnection()->getDriverName()) {
				'pgsql' => 'C',
				'mysql', 'mariadb' => 'utf8mb4_bin',
				default => null,
			});
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('board_user', function (Blueprint $table) {
			$table->dropColumn('position');
		});
	}
};
