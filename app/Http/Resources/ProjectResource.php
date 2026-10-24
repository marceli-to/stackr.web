<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'description' => $this->description,
			'url' => $this->url,
			'color' => $this->color,
			// The file name changes with every upload, so the URL can be cached for good.
			'background_url' => $this->background_path !== null
				? route('projects.background', ['project' => $this->resource, 'v' => pathinfo($this->background_path, PATHINFO_FILENAME)])
				: null,
		];
	}
}
