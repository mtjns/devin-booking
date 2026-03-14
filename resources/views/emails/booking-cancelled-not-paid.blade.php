<!DOCTYPE html>
<html>

<head>
    <title>Zrušení rezervace</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Dobrý den, {{ $booking->customer_name ?? '' }},</h2>

    <p>informujeme Vás, že Vaše rezervace chaty byla automaticky zrušena.</p>

    <p>Důvodem je vypršení lhůty pro zaplacení zálohy.</p>

    @include('emails.components.booking-summary', ['hidePaymentInfo' => true])

    <p>Pokud máte i nadále zájem o pobyt, je nutné vytvořit rezervaci novou.</p>

    <p>S pozdravem,<br>Tým správy chaty</p>
</body>

</html>