<div x-data="bookingManager()" x-init="init()" class="max-w-6xl mx-auto p-8 bg-white rounded-lg shadow-lg">

    <form wire:submit="submitReservation" class="space-y-10">

        <section>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">1. Termín pobytu</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" wire:ignore.self>
                <div wire:ignore>
                    <label class="block text-sm font-semibold text-gray-600 mb-2 uppercase">Příjezd a Odjezd</label>
                    <input x-ref="litepicker" type="text" readonly
                        class="w-full p-4 border-2 border-gray-100 rounded-xl bg-gray-50 focus:border-blue-500 transition-all cursor-pointer text-lg"
                        placeholder="Vyberte termín...">
                </div>
            </div>

            <div x-show="nights > 0" x-transition class="mt-4 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <span class="text-gray-700 font-medium">Dostupná kapacita pro tento termín: </span>
                <span class="font-bold text-lg" :class="availableBeds > 0 ? 'text-green-600' : 'text-red-600'"
                    x-text="availableBeds + ' lůžek'"></span>
            </div>

            @error('start_date') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">2. Hosté</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <template x-for="(label, key) in guestLabels" :key="key">
                    <div class="p-4 border-2 border-gray-100 rounded-xl bg-gray-50 flex flex-col items-center">
                        <span class="text-xs font-bold text-gray-400 uppercase mb-3" x-text="label"></span>
                        <div class="flex items-center space-x-4">
                            <button type="button" @click="decrement(key)"
                                class="w-10 h-10 rounded-full bg-white shadow-sm border flex items-center justify-center font-bold text-xl hover:bg-gray-100">-</button>
                            <span class="text-2xl font-bold w-8 text-center" x-text="guests[key]"></span>
                            <button type="button" @click="increment(key)"
                                class="w-10 h-10 rounded-full bg-white shadow-sm border flex items-center justify-center font-bold text-xl hover:bg-gray-100">+</button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="isOverCapacity" x-transition
                class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center text-red-800">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                <span>Počet hostů (<span x-text="totalGuests"></span>) překračuje dostupnou kapacitu (<span
                        x-text="availableBeds"></span>) pro zvolený termín. Prosím, upravte počet hostů nebo vyberte
                    jiný termín.</span>
            </div>
        </section>

        <section x-show="totalPrice > 0" x-transition class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-blue-900 font-bold text-lg">Předběžná kalkulace</h3>
                    <p class="text-blue-700 text-sm">Cena za <span x-text="nights"></span> nocí</p>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-black text-blue-900" x-text="formatPrice(totalPrice)"></span>
                    <p class="text-blue-600 text-xs mt-1">Včetně paušálu za dřevo a energií</p>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">3. Kontaktní údaje</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Celé jméno</label>
                    <input type="text" wire:model="customer_name"
                        class="w-full p-3 border-2 border-gray-100 rounded-xl focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">E-mail</label>
                    <input type="email" wire:model="customer_email"
                        class="w-full p-3 border-2 border-gray-100 rounded-xl focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Telefon</label>
                    <input type="tel" wire:model="customer_phone"
                        class="w-full p-3 border-2 border-gray-100 rounded-xl focus:border-blue-500 outline-none transition-all">
                </div>
            </div>
        </section>

        <section class="bg-gray-50 p-8 rounded-2xl border border-gray-200">
            <h3 class="font-bold text-gray-800 mb-4 text-lg">Důležité informace k rezervaci</h3>
            <ul class="space-y-3 text-gray-600 text-sm mb-6">
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>V ceně kalkulace je automaticky započítán fixní poplatek za spotřebu dřeva.</span>
                </li>
                <li class="flex items-start" x-show="cabinRules.pending_window">
                    <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Po odeslání rezervace je nutné uhradit zálohu nebo plnou částku do <span class="font-bold"
                            x-text="cabinRules.pending_window"></span> dnů. Jinak bude rezervace automaticky
                        stornována.</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span>Veškeré platební údaje, variabilní symbol a pokyny Vám obratem zašleme na uvedený
                        e-mail.</span>
                </li>
            </ul>

            <label class="flex items-start space-x-3 cursor-pointer pt-4 border-t border-gray-200">
                <input type="checkbox" wire:model="consent"
                    class="mt-1 w-5 h-5 rounded text-blue-600 border-gray-300 focus:ring-blue-500">
                <span class="text-gray-700 text-sm leading-relaxed">
                    Souhlasím se zpracováním osobních údajů a <a href="#"
                        class="text-blue-600 underline font-semibold">podmínkami ubytování</a>. Beru na vědomí, že mé
                    jméno a termín pobytu budou zobrazeny ve veřejném kalendáři obsazenosti. *
                </span>
            </label>
            @error('consent') <span class="text-red-500 text-xs mt-2 block">K odeslání rezervace musíte souhlasit s
            podmínkami.</span> @enderror
        </section>

        <div class="flex justify-end">
            <button type="submit" :disabled="isOverCapacity || nights === 0"
                :class="(isOverCapacity || nights === 0) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 transform hover:-translate-y-1 active:scale-95'"
                class="text-white font-black py-4 px-12 rounded-2xl shadow-xl transition-all text-lg">
                Odeslat rezervaci
            </button>
        </div>
    </form>

    @script
    <script>
        Alpine.data('bookingManager', () => ({
            cabinRules: {},
            bookings: [],
            nights: 0,
            totalPrice: 0,
            availableBeds: 0,
            guestLabels: {
                graduate: 'Absolvent',
                student: 'Student',
                child: 'Dítě',
                external: 'Externista',
                dog: 'Pes'
            },
            guests: {
                graduate: 0,
                student: 0,
                child: 0,
                external: 0,
                dog: 0
            },

            async init() {
                // Fetch the central availability data and global settings from the API
                const response = await fetch('/api/availability');
                const data = await response.json();
                this.cabinRules = data.cabin_rules;
                this.bookings = data.bookings;

                // Initialize the default available capacity assuming an empty cabin
                this.availableBeds = this.cabinRules.bed_capacity || 0;

                this.initPicker();

                // Trigger a price recalculation anytime a guest counter is modified
                this.$watch('guests', () => this.calculatePrice(), { deep: true });
            },

            initPicker() {
                // Calculates the maximum allowable booking date exactly one year from the current date
                const maxAllowedDate = new Date();
                maxAllowedDate.setFullYear(maxAllowedDate.getFullYear() + 1);

                const picker = new Litepicker({
                    element: this.$refs.litepicker,
                    singleMode: false,
                    numberOfMonths: window.innerWidth > 768 ? 2 : 1,
                    numberOfColumns: window.innerWidth > 768 ? 2 : 1,
                    // Locks the earliest selectable date to the current day
                    minDate: new Date(),
                    // Locks the latest selectable date to one year in the future
                    maxDate: maxAllowedDate,
                    format: 'DD.MM.YYYY',
                    lang: 'cs-CZ',
                    setup: (picker) => {
                        picker.on('selected', (date1, date2) => {
                            const start = date1.format('YYYY-MM-DD');
                            const end = date2.format('YYYY-MM-DD');

                            this.nights = Math.ceil((date2.getTime() - date1.getTime()) / (1000 * 60 * 60 * 24));

                            $wire.set('start_date', start);
                            $wire.set('end_date', end);

                            this.calculateAvailability(start, end);
                            this.calculatePrice();
                        });
                    }
                });
            },

            // Calculates how many beds remain available within the user's selected date range
            calculateAvailability(startStr, endStr) {
                let start = new Date(startStr);
                let end = new Date(endStr);

                // Track the "busiest" night within the selected date range
                let maxReserved = 0;
                let isLocked = false;

                // Loop through every single night of the requested stay
                for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                    let currentStr = this.formatDate(d);
                    let reservedForNight = 0;

                    // Check the current night against every active booking from the API
                    for (let i = 0; i < this.bookings.length; i++) {
                        let b = this.bookings[i];

                        // If the existing booking overlaps with the current night
                        if (currentStr >= b.start_date && currentStr < b.end_date) {
                            // If any night is entirely blocked, the whole range is invalid
                            if (b.reserve_whole) {
                                isLocked = true;
                                break;
                            }
                            // Accumulate the number of beds taken on this specific night
                            reservedForNight += b.reserved_beds;
                        }
                    }

                    // Stop checking future nights if we hit a hard lockout
                    if (isLocked) break;

                    // Update the max peak constraint. The available beds for the whole trip 
                    // is limited by the single busiest night in the range.
                    if (reservedForNight > maxReserved) {
                        maxReserved = reservedForNight;
                    }
                }

                // Update the UI state based on the loop results
                if (isLocked) {
                    this.availableBeds = 0;
                } else {
                    this.availableBeds = this.cabinRules.bed_capacity - maxReserved;
                }
            },

            // Helper function to format JS dates into YYYY-MM-DD consistently without timezone shifting bugs
            formatDate(date) {
                const d = new Date(date);
                let month = '' + (d.getMonth() + 1);
                let day = '' + d.getDate();
                const year = d.getFullYear();

                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;

                return [year, month, day].join('-');
            },

            // Computed property summarizing total guests selected by the user
            get totalGuests() {
                return this.guests.graduate + this.guests.student + this.guests.child + this.guests.external;
            },

            // Evaluates if the form should be locked down due to capacity constraints
            get isOverCapacity() {
                return this.nights > 0 && this.totalGuests > this.availableBeds;
            },

            // Safely increment a guest category and sync to Livewire
            increment(key) {
                this.guests[key]++;
                $wire.set(key + '_count', this.guests[key]);
            },

            // Safely decrement a guest category, preventing negative numbers, and sync to Livewire
            decrement(key) {
                if (this.guests[key] > 0) {
                    this.guests[key]--;
                    $wire.set(key + '_count', this.guests[key]);
                }
            },

            // Client-side estimation of the total price
            calculatePrice() {
                if (this.nights === 0) return;

                let nightlySum = 0;
                const p = this.cabinRules.prices;

                // Multiply guest counts by their respective category prices
                nightlySum += this.guests.graduate * p.graduate;
                nightlySum += this.guests.student * p.student;
                nightlySum += this.guests.child * p.child;
                nightlySum += this.guests.external * p.external;
                if (p.dog) {
                    nightlySum += this.guests.dog * p.dog;
                }

                // Add the flat nightly fee for wood consumption
                if (p.wood) {
                    nightlySum += p.wood;
                }

                this.totalPrice = nightlySum * this.nights;
            },

            formatPrice(val) {
                return new Intl.NumberFormat('cs-CZ', { style: 'currency', currency: 'CZK' }).format(val);
            }
        }));
    </script>
    @endscript
</div>