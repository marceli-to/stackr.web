<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Whether a user wants notifications by email too, and when a card's
	 * members were last reminded of its due date.
	 */
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->boolean('email_notifications')->default(true)->after('email_verified_at');
		});

		Schema::table('cards', function (Blueprint $table) {
			$table->timestamp('due_reminded_at')->nullable()->after('due_at');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('cards', function (Blueprint $table) {
			$table->dropColumn('due_reminded_at');
		});

		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('email_notifications');
		});
	}
};
