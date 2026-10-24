<?php

use App\Enums\BoardColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table('boards', function (Blueprint $table) {
			$table->string('color', 16)->default(BoardColor::Cobalt->value)->after('description');
		});

		// Existing boards cycle through the colours in the order they were made.
		$cases = BoardColor::cases();

		foreach (DB::table('boards')->orderBy('id')->pluck('id')->values() as $i => $id) {
			DB::table('boards')->where('id', $id)->update(['color' => $cases[$i % count($cases)]->value]);
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('boards', function (Blueprint $table) {
			$table->dropColumn('color');
		});
	}
};
