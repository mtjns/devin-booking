<!DOCTYPE html>
<html>

<head>
    <title>Záloha přijata</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Dobrý den, {{ $booking->customer_name }},</h2>

    <p>potvrzujeme, že jsme úspěšně přijali plnou zálohu za váš pobyt.</p>

    <p>Vaše rezervace na chatě Děvín je tímto plně potvrzena.</p>

    @include('emails.components.booking-summary')

    <p>Případný doplatek do celkové ceny ubytování
        ({{ number_format($booking->total_price - $booking->paid_amount, 0, ',', ' ') }} Kč) uhradíte v hotovosti
        správci při příjezdu na chatu</p>

    <p>Těšíme se na Vaši návštěvu!</p>
    <br>
    <p>S pozdravem,<br>Tým chaty Děvín</p>
</body>

</html>