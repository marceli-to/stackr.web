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
		Schema::create('board_invitations', function (Blueprint $table) {
			$table->id();
			$table->foreignId('board_id')->constrained()->cascadeOnDelete();
			$table->string('email');
			$table->string('token', 64)->unique();
			$table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
			$table->timestamps();

			$table->unique(['board_id', 'email']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('board_invitations');
	}
};
