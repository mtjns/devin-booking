<!DOCTYPE html>
<html>

<head>
    <title>System Error</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333; background-color: #fce4e4; padding: 20px;">
    <h2 style="color: #c0392b;">Critical System Error Detected</h2>

    <p>The Děvín Booking System encountered a fatal error. Immediate attention is required.</p>

    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; padding: 15px;">
        <tr>
            <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd; width: 120px;">Source:</td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $source }}</td>
        </tr>
        @if(!empty($jobContext))
            <tr>
                <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd;">Context/Job:</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #d35400;">{{ $jobContext }}</td>
            </tr>
        @endif
        <tr>
            <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd;">Exception:</td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #c0392b;">{{ $errorClass }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd;">Message:</td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #c0392b;">{{ $errorMessage }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd;">File:</td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $errorFile }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd;">Line:</td>
            <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $errorLine }}</td>
        </tr>
        @if(!empty($actionRequired))
            <tr>
                <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd; vertical-align: top;">Action
                    Required:</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold;">{{ $actionRequired }}</td>
            </tr>
        @endif
    </table>

    @if(!empty($bookingDetails))
        <h3 style="color: #2c3e50; margin-top: 25px;">Booking Information</h3>
        <table style="width: 100%; border-collapse: collapse; background-color: #fff; padding: 15px;">
            @foreach($bookingDetails as $key => $value)
                <tr>
                    <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd; width: 150px;">{{ $key }}:</td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if(!empty($additionalContext))
        <h3 style="color: #2c3e50; margin-top: 25px;">Additional Context</h3>
        <table style="width: 100%; border-collapse: collapse; background-color: #fff; padding: 15px;">
            @foreach($additionalContext as $key => $value)
                <tr>
                    <td style="font-weight: bold; padding: 8px; border-bottom: 1px solid #ddd; width: 150px;">{{ $key }}:</td>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>

</html>