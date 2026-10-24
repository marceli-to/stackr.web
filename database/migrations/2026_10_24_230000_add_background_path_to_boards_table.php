<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * An optional photo behind the board, on top of the board's colour.
	 */
	public function up(): void
	{
		Schema::table('boards', function (Blueprint $table) {
			$table->string('background_path')->nullable()->after('color');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('boards', function (Blueprint $table) {
			$table->dropColumn('background_path');
		});
	}
};
