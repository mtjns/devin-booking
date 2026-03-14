@php
    $settings = app(\App\Settings\GeneralSettings::class);
    $remaining_deposit = max(0, $booking->deposit_amount - $booking->paid_amount);
@endphp

<div style="background-color: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; font-family: Arial, sans-serif; color: #333;">
    <h3 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Přehled rezervace</h3>
    
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <tr>
            <th style="padding: 5px 0;">Stav:</th>
            <td style="padding: 5px 0;">
                @if($booking->status === 'pending')
                    <span style="color: #d97706; font-weight: bold;">Čeká na zaplacení zálohy</span>
                @elseif($booking->status === 'deposit_paid')
                    <span style="color: #059669; font-weight: bold;">Záloha zaplacena (Potvrzeno)</span>
                @elseif($booking->status === 'cancelled')
                    <span style="color: #dc2626; font-weight: bold;">Zrušeno</span>
                @else
                    {{ $booking->status }}
                @endif
            </td>
        </tr>
        <tr>
            <th style="padding: 5px 0;">Termín:</th>
            <td style="padding: 5px 0;">{{ \Carbon\Carbon::parse($booking->start_date)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($booking->end_date)->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <th style="padding: 5px 0;">Hosté:</th>
            <td style="padding: 5px 0;">
                @php
                    $guests = [];
                    if ($booking->graduate_count > 0) $guests[] = $booking->graduate_count . ' absolvent/ů';
                    if ($booking->student_count > 0) $guests[] = $booking->student_count . ' student/ů';
                    if ($booking->child_count > 0) $guests[] = $booking->child_count . ' dětí';
                    if ($booking->external_count > 0) $guests[] = $booking->external_count . ' externistů';
                @endphp
                {{ implode(', ', $guests) ?: 'Nezadáno' }}
                @if($booking->dog_count > 0)
                    (+ {{ $booking->dog_count }} pes/psi)
                @endif
            </td>
        </tr>
        <tr>
            <th style="padding: 5px 0;">Celková cena za pobyt:</th>
            <td style="padding: 5px 0;">{{ number_format($booking->total_price, 0, ',', ' ') }} Kč</td>
        </tr>
        <tr>
            <th style="padding: 5px 0;">Zaplaceno:</th>
            <td style="padding: 5px 0;">{{ number_format($booking->paid_amount, 0, ',', ' ') }} Kč</td>
        </tr>
        <tr>
            <th style="padding: 5px 0; border-top: 1px solid #eee;">Požadovaná záloha celkem:</th>
            <td style="padding: 5px 0; border-top: 1px solid #eee;">{{ number_format($booking->deposit_amount, 0, ',', ' ') }} Kč</td>
        </tr>
        @if($remaining_deposit > 0)
        <tr>
            <th style="padding: 5px 0; color: #d97706;">Zbývá doplatit na záloze:</th>
            <td style="padding: 5px 0; color: #d97706; font-weight: bold;">{{ number_format($remaining_deposit, 0, ',', ' ') }} Kč</td>
        </tr>
        @endif
    </table>
</div>

@if($booking->status === 'pending' && $remaining_deposit > 0 && !isset($hidePaymentInfo))
<div style="border: 2px solid #e5e7eb; padding: 15px; border-radius: 8px; margin: 20px 0; font-family: Arial, sans-serif;">
    <h3 style="margin-top: 0; color: #111827;">Platební údaje pro úhradu zálohy</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 10px 0; vertical-align: top; width: 60%;">
                <ul style="margin: 0; padding-left: 20px; line-height: 1.6;">
                    <li>Částka k úhradě: <strong>{{ number_format($remaining_deposit, 0, ',', ' ') }} Kč</strong></li>
                    <li>Číslo účtu: <strong>{{ $settings->bank_account_number }} / {{ $settings->bank_code }}</strong></li>
                    <li>Variabilní symbol: <strong>{{ $booking->variable_symbol }}</strong></li>
                </ul>
            </td>
            @if(isset($qrImageBinary) && !empty($qrImageBinary))
            <td style="padding: 10px 0; text-align: right; vertical-align: top; width: 40%;">
                <img src="{{ $message->embedData($qrImageBinary, 'qr-platba.png') }}" alt="QR Platba"
                    style="max-width: 130px; height: auto; border: 1px solid #d1d5db; border-radius: 4px; padding: 5px; background-color: #ffffff;">
                <div style="font-size: 11px; color: #6b7280; margin-top: 5px; text-align: center; width: 130px; float: right;">
                    Naskenujte v aplikaci
                </div>
            </td>
            @endif
        </tr>
    </table>
</div>
@endif