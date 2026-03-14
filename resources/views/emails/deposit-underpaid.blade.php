<!DOCTYPE html>
<html>

<head>
    <title>Nedoplatek zálohy</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Dobrý den, {{ $booking->customer_name }},</h2>

    <p>zaznamenali jsme Vaši platbu ve výši <strong>{{ number_format($booking->paid_amount, 0, ',', ' ') }} Kč</strong>
        k rezervaci chaty Děvín (Variabilní symbol: {{ $booking->variable_symbol }}).</p>

    <p>Tato částka bohužel nepokrývá minimální požadovanou zálohu pro potvrzení termínu.</p>

    @include('emails.components.booking-summary')

    <p>Děkujeme za pochopení.</p>
    <br>
    <p>S pozdravem,<br>Tým chaty Děvín</p>
</body>

</html>