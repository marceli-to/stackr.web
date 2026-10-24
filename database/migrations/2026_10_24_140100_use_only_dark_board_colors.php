<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	/**
	 * The two light colours make way for dark ones, so text on every
	 * board colour is white.
	 *
	 * @var array<string, string>
	 */
	private array $map = ['signal' => 'rust', 'ochre' => 'plum'];

	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		foreach ($this->map as $from => $to) {
			DB::table('boards')->where('color', $from)->update(['color' => $to]);
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		foreach ($this->map as $from => $to) {
			DB::table('boards')->where('color', $to)->update(['color' => $from]);
		}
	}
};
