<!DOCTYPE html>
<html>
<head>
    <title>Úprava Vaší rezervace</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Úprava Vaší rezervace</h2>
    <p>Vážená/ý {{ $booking->customer_name }},</p>

    <p>informujeme Vás, že ve Vaší rezervaci chaty došlo ke změnám. Ty se mohou týkat termínu, počtu osob nebo celkové částky k úhradě.</p>

    @include('emails.components.booking-summary')

    <p>V případě nesrovnalostí nebo dotazů nás neváhejte kontaktovat.</p>

    <p>S pozdravem,<br>Tým správy chaty</p>
</body>
</html>