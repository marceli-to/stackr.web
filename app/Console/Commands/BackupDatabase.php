<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Writes a consistent, gzipped snapshot of the SQLite database to storage/app/private and removes old ones.
 * Runs nightly at 03:00 from the server's crontab (see progress.md, "Production").
 */
#[Signature('app:backup-database {--days=30 : Delete backups older than this many days}')]
#[Description('Create a compressed backup of the SQLite database')]
class BackupDatabase extends Command
{
	public function handle(): int
	{
		$directory = storage_path('app/private');
		File::ensureDirectoryExists($directory);

		$snapshot = $directory.'/database_'.now()->format('Y-m-d_H-i-s').'.sqlite';

		// VACUUM INTO produces a consistent copy even while the site is writing to the database.
		DB::statement('VACUUM INTO ?', [$snapshot]);

		$archive = $snapshot.'.gz';
		file_put_contents($archive, gzencode(File::get($snapshot), 9));
		File::delete($snapshot);

		$this->info('Backup created: '.basename($archive).' ('.number_format(filesize($archive) / 1024, 1).' KB)');

		$this->deleteOldBackups($directory);

		return self::SUCCESS;
	}

	private function deleteOldBackups(string $directory): void
	{
		$cutoff = now()->subDays((int) $this->option('days'))->getTimestamp();

		foreach (File::glob($directory.'/database_*.sqlite.gz') as $file) {
			if (filemtime($file) < $cutoff) {
				File::delete($file);
				$this->line('Deleted old backup: '.basename($file));
			}
		}
	}
}
