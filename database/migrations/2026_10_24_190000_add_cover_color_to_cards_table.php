<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * A plain colour cover, as an alternative to an image.
	 */
	public function up(): void
	{
		Schema::table('cards', function (Blueprint $table) {
			$table->string('cover_color', 20)->nullable()->after('cover_attachment_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('cards', function (Blueprint $table) {
			$table->dropColumn('cover_color');
		});
	}
};
