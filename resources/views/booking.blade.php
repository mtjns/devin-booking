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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/cs.global.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

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

            /* Litepicker Dark Mode overrides */
            .litepicker {
                background-color: #1F2937 !important;
                border-color: #374151 !important;
                color: #F9FAFB !important;
            }

            .litepicker .container__months .month-item-name,
            .litepicker .container__months .month-item-year {
                color: #F9FAFB !important;
            }

            .litepicker .container__months .month-item-weekdays-row>div {
                color: #9CA3AF !important;
            }

            .litepicker .container__days .day-item {
                color: #D1D5DB !important;
            }

            .litepicker .container__days .day-item:hover {
                color: #1C64F2 !important;
                box-shadow: inset 0 0 0 1px #1C64F2 !important;
            }

            .litepicker .container__days .day-item.is-today {
                color: #FCA5A5 !important;
            }

            .litepicker .container__days .day-item.is-locked {
                color: #4B5563 !important;
                background-color: transparent !important;
            }

            .litepicker .container__days .day-item.is-in-range {
                background-color: #374151 !important;
            }

            .litepicker .container__days .day-item.is-start-date,
            .litepicker .container__days .day-item.is-end-date {
                background-color: #1C64F2 !important;
                color: #ffffff !important;
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

    <div class="max-w-4xl mx-auto px-4 mb-8">
        <div
            class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="text-lg font-medium text-heading dark:text-white mb-6">Dostupnost kapacity</h2>
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
                        .then(response => response.json())
                        .then(data => {
                            const events = [];

                            data.bookings.forEach(booking => {
                                if (booking.reserve_whole) {
                                    events.push({
                                        title: booking.customer_name + ' (Celá chata)',
                                        start: booking.start_date,
                                        end: booking.end_date,
                                        color: '#EF4444',
                                        display: 'block'
                                    });
                                } else {
                                    events.push({
                                        title: booking.customer_name + ' (' + luzkaText(booking.reserved_beds) + ')',
                                        start: booking.start_date,
                                        end: booking.end_date,
                                        color: '#3F83F8',
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