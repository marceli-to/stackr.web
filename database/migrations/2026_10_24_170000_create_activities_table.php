<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * What happened to a card, and who did it.
	 */
	public function up(): void
	{
		Schema::create('activities', function (Blueprint $table) {
			$table->id();
			$table->foreignId('card_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
			$table->string('type', 40);
			$table->json('data')->nullable();
			$table->timestamp('created_at')->nullable();
			$table->index(['card_id', 'id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('activities');
	}
};
