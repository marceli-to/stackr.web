<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * An import whose files are still being fetched, one request at a time.
	 */
	public function up(): void
	{
		Schema::create('board_imports', function (Blueprint $table) {
			$table->id();
			$table->foreignId('board_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('source', 20);
			$table->json('pending');
			$table->json('failed');
			$table->unsignedInteger('total');
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('board_imports');
	}
};
