<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * The board's code repository, so commit hashes in comments and
	 * descriptions link to their commits.
	 */
	public function up(): void
	{
		Schema::table('boards', function (Blueprint $table) {
			$table->string('repository_url', 2048)->nullable()->after('url');
		});
	}

	public function down(): void
	{
		Schema::table('boards', function (Blueprint $table) {
			$table->dropColumn('repository_url');
		});
	}
};
