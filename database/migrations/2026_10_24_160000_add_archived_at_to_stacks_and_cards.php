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
		Schema::table('stacks', function (Blueprint $table) {
			$table->timestamp('archived_at')->nullable()->after('position');
		});

		Schema::table('cards', function (Blueprint $table) {
			$table->timestamp('archived_at')->nullable()->after('completed_at');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('cards', function (Blueprint $table) {
			$table->dropColumn('archived_at');
		});

		Schema::table('stacks', function (Blueprint $table) {
			$table->dropColumn('archived_at');
		});
	}
};
