<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace - Chata Děvín</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Calendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/cs.global.min.js"></script>

    <!-- Calendar input library -->
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

    @livewireStyles
</head>

<body class="bg-gray-100 py-10">

    <div class="max-w-6xl mx-auto mb-6 text-center">
        <h1 class="text-3xl font-bold text-gray-800">Rezervace ubytování</h1>
        <p class="text-gray-600 mt-2">Chata Děvín</p>
    </div>

    <div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Dostupnost kapacity</h2>
        <div id="availability-calendar"></div>
    </div>

    <div class="max-w-6xl mx-auto">
        <livewire:booking-form />
    </div>

    @livewireScripts

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('availability-calendar');

            // Evaluates the viewport width to determine if the device is a mobile phone
            const isMobile = window.innerWidth < 768;

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'cs',
                firstDay: 1,

                // Adapts the header toolbar structure to prevent overflow on narrow screens
                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: 'today'
                },

                // Formats the title
                titleFormat: {
                    month: 'short',
                    year: 'numeric'
                },

                // Condenses button text on mobile to save horizontal space
                height: 'auto',

                events: function (info, successCallback, failureCallback) {
                    fetch('/api/availability')
                        .then(response => response.json())
                        .then(data => {
                            const events = [];

                            data.bookings.forEach(booking => {
                                if (booking.reserve_whole) {
                                    events.push({
                                        title: booking.customer_name + ' (Celá chata)',
                                        start: booking.start_date,
                                        end: booking.end_date,
                                        color: '#ef4444',
                                        display: 'block'
                                    });
                                } else {
                                    events.push({
                                        title: booking.customer_name + ' (' + booking.reserved_beds + ' lůžek)',
                                        start: booking.start_date,
                                        end: booking.end_date,
                                        color: '#f59e0b',
                                        display: 'block'
                                    });
                                }
                            });

                            successCallback(events);
                        })
                        .catch(error => {
                            console.error('Error fetching calendar data:', error);
                            failureCallback(error);
                        });
                }
            });

            calendar.render();
        });
    </script>
</body>

</html>