<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * A smaller copy of large images, for covers and previews.
	 */
	public function up(): void
	{
		Schema::table('attachments', function (Blueprint $table) {
			$table->string('thumb_path')->nullable()->after('path');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('attachments', function (Blueprint $table) {
			$table->dropColumn('thumb_path');
		});
	}
};
