<?php

use App\Enums\ProjectColor;
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
		Schema::table('projects', function (Blueprint $table) {
			$table->string('color', 16)->default(ProjectColor::Cobalt->value)->after('description');
		});

		// Existing projects cycle through the colours in the order they were made.
		$cases = ProjectColor::cases();

		foreach (DB::table('projects')->orderBy('id')->pluck('id')->values() as $i => $id) {
			DB::table('projects')->where('id', $id)->update(['color' => $cases[$i % count($cases)]->value]);
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('projects', function (Blueprint $table) {
			$table->dropColumn('color');
		});
	}
};
