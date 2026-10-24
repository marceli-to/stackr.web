<x-mail::message>
# You're on {{ $project }}

{{ $inviter }} added you to **{{ $project }}** in {{ config('app.name') }}.

<x-mail::button :url="$url">
Open the board
</x-mail::button>
</x-mail::message>
