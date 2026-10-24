<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * How many cards a list should hold at most (work in progress). A hint,
	 * not a rule: the list shows when it's over.
	 */
	public function up(): void
	{
		Schema::table('stacks', function (Blueprint $table) {
			$table->unsignedSmallInteger('wip_limit')->nullable()->after('position');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('stacks', function (Blueprint $table) {
			$table->dropColumn('wip_limit');
		});
	}
};
