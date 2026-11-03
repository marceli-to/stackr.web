<?php

namespace App\Actions\Imports;

use App\Actions\Attachments\MakeThumbnail;
use App\Models\Attachment;
use App\Models\BoardImport;
use App\Models\Card;
use finfo;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DownloadImportFile
{
	public function __construct(private MakeThumbnail $makeThumbnail) {}

	/**
	 * Fetches the import's next file from Trello and attaches it to its
	 * card, with its uploader and date. A file Trello can't deliver is
	 * noted as failed and skipped; a rejected key or token throws and
	 * leaves the file pending, so the import can go on with new ones.
	 * The import is deleted once nothing is pending.
	 *
	 * @return array{name: string|null, done: int, total: int, failed: list<array{name: string, card: string, reason: string}>, finished: bool}
	 */
	public function handle(BoardImport $import, string $key, string $token): array
	{
		$pending = $import->pending;
		$file = array_shift($pending);

		if ($file !== null) {
			$card = Card::find($file['card_id']);

			if ($card !== null) {
				$response = Http::withHeaders(['Authorization' => "OAuth oauth_consumer_key=\"{$key}\", oauth_token=\"{$token}\""])
					->timeout(120)
					->retry(2, 1000, fn (Throwable $e) => ! ($e instanceof RequestException && in_array($e->response->status(), [401, 403, 404], true)), throw: false)
					->get($file['url']);

				if (in_array($response->status(), [401, 403], true)) {
					throw new RuntimeException('Trello rejected the API key or token.');
				}

				if ($response->successful()) {
					$this->attach($card, $file, $response->body());
				} else {
					$import->failed = [...$import->failed, ['name' => $file['name'], 'card' => $card->title, 'reason' => "Trello answered {$response->status()}"]];
				}
			}

			$import->pending = $pending;
		}

		$result = [
			'name' => $file['name'] ?? null,
			'done' => $import->total - count($import->pending),
			'total' => $import->total,
			'failed' => $import->failed,
			'finished' => $import->pending === [],
		];

		$result['finished'] ? $import->delete() : $import->save();

		return $result;
	}

	/**
	 * @param  array{card_id: int, url: string, name: string, file_name: string, mime_type: string|null, user_id: int, date: string, cover: bool}  $file
	 */
	private function attach(Card $card, array $file, string $bytes): void
	{
		$extension = Str::lower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
		$path = Attachment::directoryFor($card->stack->board_id, $card->id).'/'.Str::uuid().($extension !== '' ? ".{$extension}" : '');
		Storage::disk(Attachment::DISK)->put($path, $bytes);

		$mime = $file['mime_type'] ?? ((new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream');
		$size = str_starts_with($mime, 'image/') ? @getimagesizefromstring($bytes) : false;
		$date = Carbon::parse($file['date']);

		$attachment = new Attachment([
			'path' => $path,
			'name' => $file['name'],
			'mime_type' => Str::limit($mime, 127, ''),
			'size' => strlen($bytes),
			'width' => $size !== false ? $size[0] : null,
			'height' => $size !== false ? $size[1] : null,
		]);
		$attachment->card()->associate($card);
		$attachment->user_id = $file['user_id'];
		$attachment->created_at = $date;
		$attachment->updated_at = $date;
		$attachment->save();

		$this->makeThumbnail->handle($attachment);
		ImportTrelloBoard::activity($card, 'attached', ['name' => $attachment->name], $attachment->user()->firstOrFail(), $date);

		if ($file['cover'] && $attachment->isImage()) {
			$card->timestamps = false;
			$card->update(['cover_attachment_id' => $attachment->id]);
		}
	}
}
