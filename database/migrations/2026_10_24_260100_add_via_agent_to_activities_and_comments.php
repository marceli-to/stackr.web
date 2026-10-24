<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Marks what an agent did with one of its user's tokens, so the card's
	 * history can say so.
	 */
	public function up(): void
	{
		Schema::table('activities', function (Blueprint $table) {
			$table->boolean('via_agent')->default(false)->after('data');
		});

		Schema::table('comments', function (Blueprint $table) {
			$table->boolean('via_agent')->default(false)->after('body');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('activities', function (Blueprint $table) {
			$table->dropColumn('via_agent');
		});

		Schema::table('comments', function (Blueprint $table) {
			$table->dropColumn('via_agent');
		});
	}
};
