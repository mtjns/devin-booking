<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace - Chata Děvín</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            darkMode: 'media',
            theme: {
                extend: {
                    colors: {
                        brand: '#1C64F2',
                        'brand-strong': '#1A56DB',
                        'brand-medium': '#3F83F8',
                        'brand-soft': '#E1EFFE',
                        'fg-brand': '#1C64F2',
                        heading: '#111827',
                        body: '#6B7280',
                        'neutral-secondary-medium': '#F9FAFB',
                        'default-medium': '#D1D5DB',
                        'success-soft': '#F0FDF4',
                        'success-subtle': '#86EFAC',
                        success: '#22C55E',
                        'fg-success-strong': '#15803D',
                        'danger-soft': '#FEF2F2',
                        'danger-subtle': '#FCA5A5',
                        danger: '#EF4444',
                        'fg-danger-strong': '#B91C1C',
                    },
                    borderRadius: {
                        base: '0.5rem',
                        xs: '0.125rem',
                    },
                    boxShadow: {
                        xs: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }

        function luzkaText(beds) {
            if (beds === 1) return '1 lůžko';
            if (beds >= 2 && beds <= 4) return beds + ' lůžka';
            return beds + ' lůžek';
        }

        function calendarEventTitle(booking) {
            text = booking.customer_name + ' (';
            if (booking.reserve_whole) {
                text += 'Celá chata'
            } else {
                text += luzkaText(booking.reserved_beds);
            }
            if (booking.status == "pending") {
                text += '; rezervace';
            } else if (booking.status == "deposit_paid") {
                text += '; potvrzeno';
            }
            text += ')';
            return text;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/cs.global.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
        media="(prefers-color-scheme: light)">

    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css"
        media="(prefers-color-scheme: dark)">

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/cs.js"></script>

    @livewireStyles

    <style>
        .fc {
            font-family: inherit;
            color: #111827;
        }

        .fc .fc-button-primary {
            background-color: #F9FAFB !important;
            border-color: #D1D5DB !important;
            color: #111827 !important;
            text-transform: capitalize;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }

        .fc .fc-button-primary:hover {
            background-color: #F3F4F6 !important;
        }

        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #E5E7EB !important;
            border-color: #D1D5DB !important;
            box-shadow: none !important;
        }

        .fc-theme-standard th {
            border-color: #E5E7EB;
            padding: 0.5rem 0;
            font-weight: 500;
            color: #6B7280;
        }

        .fc-theme-standard td,
        .fc-theme-standard th,
        .fc-theme-standard .fc-scrollgrid {
            border-color: #E5E7EB;
        }

        .fc .fc-toolbar-title {
            font-size: 1.25rem !important;
            font-weight: 600;
            color: #111827;
        }

        @media (prefers-color-scheme: dark) {

            /* FullCalendar Dark Mode overrides */
            .fc {
                color: #F9FAFB;
                --fc-border-color: #374151;
                --fc-page-bg-color: #1F2937;
                --fc-neutral-bg-color: #374151;
                --fc-today-bg-color: rgba(55, 65, 81, 0.5);
            }

            .fc a,
            .fc .fc-col-header-cell-cushion,
            .fc .fc-daygrid-day-number {
                color: #F9FAFB !important;
                text-decoration: none;
            }

            .fc .fc-button-primary {
                background-color: #374151 !important;
                border-color: #4B5563 !important;
                color: #F9FAFB !important;
            }

            .fc .fc-button-primary:hover {
                background-color: #4B5563 !important;
            }

            .fc .fc-button-primary:not(:disabled):active,
            .fc .fc-button-primary:not(:disabled).fc-button-active {
                background-color: #1F2937 !important;
                border-color: #4B5563 !important;
            }

            .fc-theme-standard th {
                border-color: #374151;
                color: #9CA3AF;
            }

            .fc-theme-standard td,
            .fc-theme-standard th,
            .fc-theme-standard .fc-scrollgrid {
                border-color: #374151;
            }

            .fc .fc-toolbar-title {
                color: #F9FAFB;
            }

            .fc-day-today {
                background-color: rgba(55, 65, 81, 0.5) !important;
            }

            .fc-day-other .fc-daygrid-day-top {
                opacity: 0.3;
            }
        }
    </style>
</head>

<body
    class="bg-neutral-secondary-medium dark:bg-gray-900 py-10 font-sans antialiased text-body dark:text-gray-400 transition-colors">

    <div class="max-w-4xl mx-auto mb-8 text-center px-4">
        <h1 class="text-3xl font-bold text-heading dark:text-white tracking-tight">Rezervace ubytování</h1>
        <p class="text-body dark:text-gray-400 mt-2 text-sm">Chata Děvín</p>
    </div>

    <!-- API request failed message -->
    <div x-data="{ apiError: false }" id="api-error" class="max-w-4xl mx-auto mb-8 px-4" x-show="apiError">
        <div class="p-6 bg-danger-soft dark:bg-gray-700 border border-danger rounded-base shadow-xs">
            <h2 class="text-lg dark:text-white font-medium text-danger mb-2">Chyba načítání dat</h2>
            <p class="text-sm text-danger-subtle">Nastala chyba při načítání dostupnosti. Zkuste to prosím znovu později.</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 mb-8">
        <div
            class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="text-lg font-medium text-heading text-center dark:text-white mb-6">Kalendář dostupnosti</h2>
            <div id="availability-calendar"></div>
            <p class="text-sm text-body dark:text-gray-400 mt-4">
                V kalendáři jsou zobrazeny pouze rezervované noci. Dny odjezdu se nezobrazují.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4">
        <livewire:booking-form />
    </div>

    @livewireScripts

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('availability-calendar');
            const isMobile = window.innerWidth < 768;

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'cs',
                firstDay: 1,
                stickyHeaderDates: false,

                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: 'today'
                },

                titleFormat: {
                    month: 'short',
                    year: 'numeric'
                },

                height: 'auto',

                events: function (info, successCallback, failureCallback) {
                    fetch('/api/availability')
                        .then(response => {
                            if (!response.ok) {
                                throw response;
                            }
                            return response.json();
                        })
                        .then(data => {
                            const events = [];

                            data.bookings.forEach(booking => {
                                if (booking.reserve_whole) {
                                    events.push({
                                        title: calendarEventTitle(booking),
                                        start: booking.start_date,
                                        end: booking.end_date,
                                        color: '#EF4444',
                                        display: 'block'
                                    });
                                } else {
                                    events.push({
                                        title: calendarEventTitle(booking),
                                        start: booking.start_date,
                                        end: booking.end_date,
                                        color: '#3F83F8',
                                        display: 'block'
                                    });
                                }
                            });

                            successCallback(events);
                            apiError = false;
                        })
                        .catch(error => {
                            if (error?.status === 429) {
                                console.warn('Availability API throttled (429).');
                            } else {
                                console.error('Error fetching calendar data:', error);
                            }
                            apiError = true;
                            failureCallback(error);
                        });
                }
            });

            calendar.render();
        });
    </script>
</body>

</html>