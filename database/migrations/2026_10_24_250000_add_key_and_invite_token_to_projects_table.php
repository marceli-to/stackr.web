<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
	/**
	 * A random key for project URLs instead of the id, and an optional link
	 * anyone can use to join.
	 */
	public function up(): void
	{
		Schema::table('projects', function (Blueprint $table) {
			$table->string('key', 12)->nullable()->after('id');
			$table->string('invite_token', 64)->nullable()->unique();
		});

		DB::table('projects')->whereNull('key')->pluck('id')->each(
			fn (int $id) => DB::table('projects')->where('id', $id)->update(['key' => Str::lower(Str::random(10))]),
		);

		Schema::table('projects', function (Blueprint $table) {
			$table->string('key', 12)->nullable(false)->unique()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('projects', function (Blueprint $table) {
			$table->dropUnique(['key']);
			$table->dropUnique(['invite_token']);
			$table->dropColumn(['key', 'invite_token']);
		});
	}
};
