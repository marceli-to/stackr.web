<x-mail::message>
# You're invited to {{ $board }}

{{ $inviter ? $inviter.' invited you' : 'You were invited' }} to work together on **{{ $board }}** in {{ config('app.name') }}.

<x-mail::button :url="$url">
Join the board
</x-mail::button>

No account yet? The link lets you create one with this email address. It works for {{ $days }} days.

If you weren't expecting this, you can ignore the email.
</x-mail::message>
