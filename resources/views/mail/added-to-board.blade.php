<x-mail::message>
# You're on {{ $board }}

{{ $inviter }} added you to **{{ $board }}** in {{ config('app.name') }}.

<x-mail::button :url="$url">
Open the board
</x-mail::button>
</x-mail::message>
