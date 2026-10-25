<?php

namespace App\Http\Controllers;

use App\Actions\Boards\ExportBoard;
use App\Models\Board;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BoardExportController extends Controller
{
	/**
	 * Download the board with its active cards and their files as a ZIP.
	 */
	public function show(Board $board, ExportBoard $exportBoard): BinaryFileResponse
	{
		Gate::authorize('view', $board);

		$name = 'stackr-'.(Str::slug($board->name) ?: $board->key).'-'.now()->format('Y-m-d').'.zip';

		return response()
			->download($exportBoard->handle($board), $name, ['Content-Type' => 'application/zip'])
			->deleteFileAfterSend();
	}
}
