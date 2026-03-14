<!DOCTYPE html>
<html>
<head>
    <title>Potvrzení rezervace</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Potvrzení rezervace</h2>
    <p>Vážená/ý {{ $booking->customer_name }},</p>

    <p>děkujeme Vám za rezervaci. Váš termín od <strong>{{ \Carbon\Carbon::parse($booking->start_date)->format('d.m.Y') }}</strong> do
        <strong>{{ \Carbon\Carbon::parse($booking->end_date)->format('d.m.Y') }}</strong> byl úspěšně zaznamenán.
    </p>

    @if($booking->status === 'pending')
        <p>Aby byla Vaše rezervace závazná, prosíme o uhrazení zálohy do <strong>{{ app(\App\Settings\GeneralSettings::class)->pending_window }} dnů</strong>.</p>
    @else
        <p>Vaše rezervace je potvrzena a plně hrazena (záloha).</p>
    @endif

    @include('emails.components.booking-summary')

    <p>Těšíme se na Vaši návštěvu!<br>Tým správy chaty</p>
</body>
</html>