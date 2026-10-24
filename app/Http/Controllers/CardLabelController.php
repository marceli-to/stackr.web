<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CardLabelController extends Controller
{
    /**
     * Put one of the project's labels on a card.
     */
    public function store(Card $card, Label $label): RedirectResponse
    {
        $project = $card->stack->project;

        Gate::authorize('update', $project);
        abort_unless($label->project_id === $project->id, 404);

        $card->labels()->syncWithoutDetaching([$label->id]);

        return back();
    }

    /**
     * Take a label off a card.
     */
    public function destroy(Card $card, Label $label): RedirectResponse
    {
        Gate::authorize('update', $card->stack->project);

        $card->labels()->detach($label->id);

        return back();
    }
}
