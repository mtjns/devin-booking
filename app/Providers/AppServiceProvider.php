<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Booking;
use App\Observers\BookingObserver;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemCrashNotice;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // // Force HTTPS in production
        // if (config('app.env') === 'production') {
        //     URL::forceScheme('https');
        // }

        Booking::observe(BookingObserver::class);

        // Listens for any background job that exhausts its maximum retry attempts
        Queue::failing(function (JobFailed $event) {

            $recipients = config('mail.crash_emails');

            if (!empty($recipients)) {
                $jobName = $event->job->resolveName();
                $jobDisplayName = $jobName;
                $jobContext = "Worker was executing: " . $jobName;
                $bookingDetails = null;
                $additionalContext = [];

                try {
                    $payload = $event->job->payload();
                    if (!empty($payload['displayName'])) {
                        $jobDisplayName = $payload['displayName'];
                        $jobContext = "Worker was executing: " . $jobDisplayName;
                    }

                    // Try to extract booking_id from job data
                    $jobData = $payload['data']['command'] ?? null;
                    if (!empty($jobData)) {
                        // Try to find booking_id in the data
                        $decoded = unserialize($jobData);
                        if (is_object($decoded) && isset($decoded->booking_id)) {
                            $booking = Booking::find($decoded->booking_id);
                            if ($booking) {
                                $bookingDetails = [
                                    'ID' => $booking->id,
                                    'Customer' => $booking->customer_name,
                                    'Email' => $booking->customer_email,
                                    'Phone' => $booking->customer_phone,
                                    'Status' => strtoupper($booking->status),
                                    'Booking Date' => $booking->booking_date?->format('Y-m-d H:i') ?? 'N/A',
                                    'Total Price' => $booking->total_price . ' Kč',
                                    'Paid Amount' => $booking->paid_amount . ' Kč',
                                ];
                                $additionalContext['Variable Symbol'] = $booking->variable_symbol;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore payload parsing errors - we'll still send the crash notice
                }

                $exceptionMsg = strtolower($event->exception->getMessage());
                $exceptionClass = strtolower(get_class($event->exception));
                $isMailJob = str_contains($jobDisplayName, 'Mail\\') || str_contains($jobDisplayName, 'Notification');

                $isMailError = str_contains($exceptionMsg, 'address in mailbox') ||
                    str_contains($exceptionMsg, 'rfc 2822') ||
                    str_contains($exceptionMsg, 'does not comply') ||
                    str_contains($exceptionMsg, '550 ') ||
                    str_contains($exceptionMsg, '553 ') ||
                    str_contains($exceptionClass, 'mailer') ||
                    str_contains($exceptionClass, 'swift');

                // Check if the failure is likely due to an invalid email address (SMTP 550, 553, RFC issues, etc.)
                if ($isMailJob && $isMailError) {
                    $actionRequired = "Požadována akce: Zákazník pravděpodobně zadal neplatnou nebo neexistující e-mailovou adresu (např. chybějící zavináč, překlep v doméně). Zkontrolujte příslušnou rezervaci v systému a opravte e-mail zákazníka. Následně můžete e-mail zkusit odeslat znovu (případně systém u pending rezervací pošle další upomínku automaticky).";
                } else {
                    $actionRequired = "Požadována akce: Úloha na pozadí (worker) selhala po všech pokusech o opakování. Zkontrolujte systémové logy (Laravel log, System log) pro zjištění příčiny (např. výpadek databáze, výpadek e-mailového serveru atd.). Poté můžete zkusit úlohu zopakovat z tabulky 'failed_jobs'.";
                }

                Mail::to($recipients)->send(new SystemCrashNotice(
                    $event->exception,
                    'Background Queue Worker',
                    $jobContext,
                    $actionRequired,
                    $bookingDetails,
                    !empty($additionalContext) ? $additionalContext : null
                ));
            }

        });




    }
}
