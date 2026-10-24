<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * People who want to hear about changes to a card, or to a whole board.
	 */
	public function up(): void
	{
		Schema::create('card_watchers', function (Blueprint $table) {
			$table->foreignId('card_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->primary(['card_id', 'user_id']);
		});

		Schema::create('project_watchers', function (Blueprint $table) {
			$table->foreignId('project_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->primary(['project_id', 'user_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('project_watchers');
		Schema::dropIfExists('card_watchers');
	}
};
