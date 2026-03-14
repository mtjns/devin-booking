<!DOCTYPE html>
<html>
<head>
    <title>Připomenutí platby za rezervaci</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Připomenutí platby za rezervaci</h2>
    <p>Vážená/ý {{ $booking->customer_name }},</p>

    <p>dovolujeme si Vám připomenout, že Vaše rezervace chaty čeká na zaplacení zálohy.</p>

    <p>Na zaplacení zbývající zálohy Vám zbývá přesně <strong>{{ $daysRemaining }}
            dní</strong>.</p>

    @include('emails.components.booking-summary')

    <p>Pokud platbu neobdržíme do konce této lhůty, systém Vaši rezervaci automaticky zruší a uvolní termín dalším
        zájemcům.</p>

    <p>Děkujeme,<br>Tým správy chaty</p>
</body>
</html>