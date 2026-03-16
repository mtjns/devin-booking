<div x-data="bookingManager()" x-init="init()" class="w-full">
    <form wire:submit="submitReservation" class="space-y-8">
        
        <!-- 1. Ceník -->
        <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Orientační ceník</h2>
            
            <!-- Grid container for simple text rows -->
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-8 text-sm text-body dark:text-gray-400">
                <template x-for="(label, key) in guestLabelsSingular" :key="key">
                    <!-- Standard pricing row -->
                    <li class="flex justify-between items-center" x-show="cabinRules.prices && cabinRules.prices[key] !== undefined">
                        <span x-text="label"></span>
                        <span class="font-medium text-heading dark:text-gray-300" x-text="formatPrice(cabinRules.prices[key]) + ' / noc'"></span>
                    </li>
                </template>
                
                <!-- Wood fee displayed alongside standard pricing rows -->
                <li class="flex justify-between items-center" x-show="cabinRules.prices && cabinRules.prices.wood">
                    <span>Dřevo</span>
                    <span class="font-medium text-heading dark:text-gray-300" x-text="formatPrice(cabinRules.prices.wood) + ' / noc'"></span>
                </li>
            </ul>
        </section>

        <!-- 2. Payment Rules & Info -->
         <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Informace</h2>
            
            <div class="space-y-4 text-sm text-body dark:text-gray-400">
                <!-- Payment Information -->
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 mt-0.5 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Po odeslání rezervace Vám zašleme e-mail s potrvzením rezervace a přesnými platebními údaji.</span>
                </div>
                
                <!-- Payment Deadline -->
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 mt-0.5 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Splatnost rezervace je <strong x-text="cabinRules.pending_window + ' dní'"></strong>. Prosíme o včasnou úhradu, v opačném případě bude rezervace automaticky stornována.</span>
                </div>

                <!-- Deposit Information -->
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 mt-0.5 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8H5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2zm-6 4v4m0 0a2 2 0 100-4 2 2 0 000 4z"/>
                    </svg>
                    <span>Pro závazné potvrzení termínu je nutné uhradit zálohu<span x-show="cabinRules.deposit_percentage"> ve výši <strong x-text="cabinRules.deposit_percentage + '%'"></strong> z celkové částky</span>. Zbytek částky se doplácí před nástupem.</span>
                </div>
            </div>
        </section>

        <!-- 4. Date Picker -->
        <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Termín pobytu</h2>
            
            <div wire:ignore.self>
                <div class="mb-5" wire:ignore>
                    <label class="block mb-2.5 text-sm font-medium text-heading dark:text-white">Příjezd a Odjezd</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-body dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01M4 4h16a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <input x-ref="litepicker" type="text" readonly
                            class="block w-full ps-9 pe-3 py-2.5 bg-neutral-secondary-medium dark:bg-gray-700 border border-default-medium dark:border-gray-600 text-heading dark:text-white text-sm rounded-base focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 shadow-xs placeholder:text-body dark:placeholder-gray-400 cursor-pointer transition-colors"
                            placeholder="Vyberte termín...">
                    </div>
                </div>
            </div>

            <div x-show="nights > 0" x-transition class="mt-2.5 text-sm">
                <span class="text-body dark:text-gray-400">Dostupná kapacita pro tento termín:</span>
                <span class="font-medium" :class="availableBeds > 0 ? 'text-fg-success-strong dark:text-green-400' : 'text-fg-danger-strong dark:text-red-400'"
                    x-text="luzkaText(availableBeds)"></span>
            </div>

            @error('start_date') 
                <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> {{ $message }}</p> 
            @enderror
        </section>

        <!-- 3. Guests -->
        <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Hosté</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="(label, key) in guestLabels" :key="key">
                    <div class="flex items-center justify-between p-3 bg-neutral-secondary-medium dark:bg-gray-700/50 border border-default-medium dark:border-gray-600 rounded-base transition-colors">
                        <div>
                            <span class="block text-sm font-medium text-heading dark:text-white" x-text="label"></span>
                        </div>
                        <div class="flex items-center">
                            <button type="button" @click="decrement(key)"
                                class="inline-flex items-center justify-center h-8 w-8 text-heading dark:text-gray-300 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-600 hover:bg-neutral-secondary-medium dark:hover:bg-gray-700 font-medium rounded-base transition-all">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                </svg>
                            </button>
                            <span class="mx-3 text-sm font-medium text-heading dark:text-white w-4 text-center" x-text="guests[key]"></span>
                            <button type="button" @click="increment(key)"
                                class="inline-flex items-center justify-center h-8 w-8 text-heading dark:text-gray-300 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-600 hover:bg-neutral-secondary-medium dark:hover:bg-gray-700 font-medium rounded-base transition-all">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="isOverCapacity" x-transition class="mt-5">
                <div class="p-3 bg-danger-soft dark:bg-red-900/30 border border-danger-subtle dark:border-red-800 rounded-base">
                    <p class="text-sm text-fg-danger-strong dark:text-red-400">
                        <span class="font-medium">Kapacita překročena!</span> K dispozici je pouze <span x-text="luzkaText(availableBeds)"></span>.
                    </p>
                </div>
            </div>
        </section>

        <!-- 4. Contact Details -->
        <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Kontaktní údaje</h2>
            
            <div class="mb-5">
                <label for="customer_name" class="block mb-2.5 text-sm font-medium @error('customer_name') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">Celé jméno</label>
                <!-- Inputs retain a standard background to prevent jarring color shifts on error, while strongly enforcing autofill text and background colors -->
                <input type="text" id="customer_name" wire:model="customer_name"
                    class="block w-full px-3 py-2.5 text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 [&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#F9FAFB] [&:-webkit-autofill]:-webkit-text-fill-color-[#111827] dark:[&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#374151] dark:[&:-webkit-autofill]:-webkit-text-fill-color-[#ffffff] @error('customer_name') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                    placeholder="Vaše jméno">
                @error('customer_name') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium">Chyba!</span> {{ $message }}</p> 
                @enderror
            </div>

            <div class="mb-5">
                <label for="customer_email" class="block mb-2.5 text-sm font-medium @error('customer_email') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">E-mail</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 @error('customer_email') text-fg-danger-strong dark:text-red-400 @else text-body dark:text-gray-400 @enderror" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                        </svg>
                    </div>
                    <input type="email" id="customer_email" wire:model="customer_email"
                        class="block w-full ps-9 pe-3 py-2.5 text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 [&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#F9FAFB] [&:-webkit-autofill]:-webkit-text-fill-color-[#111827] dark:[&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#374151] dark:[&:-webkit-autofill]:-webkit-text-fill-color-[#ffffff] @error('customer_email') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                        placeholder="vas@email.cz">
                </div>
                @error('customer_email') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium">Chyba!</span> {{ $message }}</p> 
                @enderror
            </div>

            <div class="mb-5">
                <label for="customer_phone" class="block mb-2.5 text-sm font-medium @error('customer_phone') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">Telefon</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 @error('customer_phone') text-fg-danger-strong dark:text-red-400 @else text-body dark:text-gray-400 @enderror" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.427 14.768 17.2 13.542a1.733 1.733 0 0 0-2.45 0l-.613.613a1.732 1.732 0 0 1-2.45 0l-1.838-1.84a1.735 1.735 0 0 1 0-2.452l.612-.613a1.735 1.735 0 0 0 0-2.452L9.237 5.572a1.6 1.6 0 0 0-2.45 0c-3.223 3.2-1.453 7.44 1.086 10.049 2.567 2.566 6.847 4.34 10.554 1.152a1.602 1.602 0 0 0 0-2.005Z"/>
                        </svg>
                    </div>
                    <input type="tel" id="customer_phone" wire:model="customer_phone"
                        class="block w-full ps-9 pe-3 py-2.5 text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 [&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#F9FAFB] [&:-webkit-autofill]:-webkit-text-fill-color-[#111827] dark:[&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#374151] dark:[&:-webkit-autofill]:-webkit-text-fill-color-[#ffffff] @error('customer_phone') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                        placeholder="+420 123 456 789">
                </div>
                @error('customer_phone') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium">Chyba!</span> {{ $message }}</p> 
                @enderror
            </div>

            <div class="mb-5">
                <label for="customer_notes" class="block mb-2.5 text-sm font-medium @error('customer_notes') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">Poznámka k rezervaci</label>
                <textarea id="customer_notes" wire:model="customer_notes" rows="4" 
                    class="block w-full p-3.5 min-h-[120px] text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 @error('customer_notes') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                    placeholder="Napište případné doplňující informace..."></textarea>
                @error('customer_notes') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium">Chyba!</span> {{ $message }}</p> 
                @enderror
            </div>
        </section>

        <!-- 6. Final Summary & Submit -->
        <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Shrnutí a odeslání rezervace</h2>
            
            <dl class="space-y-3 mb-6 bg-neutral-secondary-medium dark:bg-gray-700/50 p-4 rounded-base border border-default-medium dark:border-gray-600">
                <div class="flex justify-between text-sm">
                    <dt class="text-body dark:text-gray-400">Počet nocí:</dt>
                    <dd class="font-medium text-heading dark:text-white" x-text="nights"></dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-body dark:text-gray-400">Počet hostů:</dt>
                    <dd class="font-medium text-heading dark:text-white" x-text="totalGuests"></dd>
                </div>
                <div class="pt-3 border-t border-default-medium dark:border-gray-600 flex justify-between items-center">
                    <dt class="text-base font-medium text-heading dark:text-white">Předběžná celková cena:</dt>
                    <dd class="text-xl font-medium text-brand dark:text-blue-400" x-text="formatPrice(totalPrice)"></dd>
                </div>
            </dl>

            <div class="space-y-4">
                <div class="flex items-center mb-4">
                    <input wire:model="consent" id="consent" type="checkbox" class="w-4 h-4 border border-default-medium dark:border-gray-600 rounded-xs bg-neutral-secondary-medium dark:bg-gray-700 focus:ring-2 focus:ring-brand-soft dark:focus:ring-blue-600 dark:ring-offset-gray-800 cursor-pointer transition-colors">
                    <label for="consent" class="ms-2 text-sm font-medium text-heading dark:text-gray-300 select-none cursor-pointer">
                        Souhlasím s <a href="#" class="text-fg-brand dark:text-blue-400 hover:underline">podmínkami ubytování</a> a <a href="#" class="text-fg-brand dark:text-blue-400 hover:underline">zásadami ochrany osobních údajů</a>.
                    </label>
                </div>
                @error('consent') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> Musíte potvrdit souhlas.</p> 
                @enderror

                <button type="button" wire:click="submitReservation" wire:loading.attr="disabled"
                    :disabled="isOverCapacity || nights === 0"
                    class="w-full text-white bg-brand dark:bg-blue-600 box-border border border-transparent hover:bg-brand-strong dark:hover:bg-blue-700 focus:ring-4 focus:ring-brand-medium dark:focus:ring-blue-800 shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-3 focus:outline-none disabled:opacity-50 disabled:dark:bg-gray-600 disabled:cursor-not-allowed transition-colors relative">
                    
                    <span wire:loading.remove wire:target="submitReservation">Odeslat rezervaci</span>
                    
                    <span wire:loading wire:target="submitReservation" class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </div>
        </section>
    </form>

    @script
    <script>
        Alpine.data('bookingManager', () => ({
            cabinRules: {},
            bookings: [],
            nights: 0,
            totalPrice: 0,
            availableBeds: 0,
            
            guestLabelsSingular: {
                graduate: 'Absolvent',
                student: 'Student',
                child: 'Dítě',
                external: 'Externista',
                dog: 'Pes'
            },
            
            guestLabels: {
                graduate: 'Absolventů',
                student: 'Studentů',
                child: 'Dětí',
                external: 'Externistů',
                dog: 'Psů'
            },
            
            guests: {
                graduate: 0,
                student: 0,
                child: 0,
                external: 0,
                dog: 0
            },

            async init() {
                // Incorporate error handling so JS doesn't crash entirely if the fetch fails
                try {
                    const response = await fetch('/api/availability');
                    const data = await response.json();
                    this.cabinRules = data.cabin_rules || {};
                    this.bookings = data.bookings || [];
                    this.availableBeds = this.cabinRules.bed_capacity || 0;
                } catch (error) {
                    console.error('Error fetching availability:', error);
                } finally {
                    this.initPicker();
                    this.$watch('guests', () => this.calculatePrice(), { deep: true });
                }
            },

            initPicker() {
                const maxAllowedDate = new Date();
                maxAllowedDate.setFullYear(maxAllowedDate.getFullYear() + 1);

                // Collect fully reserved bookings to physically lock them in the date picker
                let lockedDays = [];
                this.bookings.forEach(b => {
                    if (b.reserve_whole) {
                        lockedDays.push([b.start_date, b.end_date]);
                    }
                });

                const picker = new Litepicker({
                    element: this.$refs.litepicker,
                    singleMode: false,
                    numberOfMonths: window.innerWidth > 768 ? 2 : 1,
                    numberOfColumns: window.innerWidth > 768 ? 2 : 1,
                    minDate: new Date(),
                    maxDate: maxAllowedDate,
                    lockDays: [],
                    format: 'DD.MM.YYYY',
                    lang: 'cs-CZ',
                    setup: (picker) => {
                        picker.on('selected', (date1, date2) => {
                            // Safely handles when a user clicks away before selecting end date
                            if (!date1 || !date2) return;

                            const start = date1.format('YYYY-MM-DD');
                            const end = date2.format('YYYY-MM-DD');
                            
                            this.nights = Math.ceil((date2.getTime() - date1.getTime()) / (1000 * 60 * 60 * 24));
                            $wire.set('start_date', start);
                            $wire.set('end_date', end);
                            
                            // Safely binds the updated value back to the input
                            if(this.$refs.litepicker) {
                                this.$refs.litepicker.value = `${date1.format('DD.MM.YYYY')} - ${date2.format('DD.MM.YYYY')}`;
                            }

                            this.calculateAvailability(start, end);
                            this.calculatePrice();
                        });
                    }
                });
            },

            calculateAvailability(startStr, endStr) {
                let start = new Date(startStr);
                let end = new Date(endStr);
                let maxReserved = 0;
                let isLocked = false;

                for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                    let currentStr = this.formatDate(d);
                    let reservedForNight = 0;

                    for (let i = 0; i < this.bookings.length; i++) {
                        let b = this.bookings[i];
                        if (currentStr >= b.start_date && currentStr < b.end_date) {
                            if (b.reserve_whole) {
                                isLocked = true;
                                break;
                            }
                            reservedForNight += b.reserved_beds;
                        }
                    }
                    if (isLocked) break;
                    if (reservedForNight > maxReserved) maxReserved = reservedForNight;
                }
                this.availableBeds = isLocked ? 0 : Math.max(0, this.cabinRules.bed_capacity - maxReserved);
            },

            formatDate(date) {
                const d = new Date(date);
                let month = '' + (d.getMonth() + 1);
                let day = '' + d.getDate();
                const year = d.getFullYear();
                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;
                return [year, month, day].join('-');
            },

            get totalGuests() {
                return this.guests.graduate + this.guests.student + this.guests.child + this.guests.external;
            },

            get isOverCapacity() {
                return this.nights > 0 && this.totalGuests > this.availableBeds;
            },

            increment(key) {
                this.guests[key]++;
                $wire.set(key + '_count', this.guests[key]);
            },

            decrement(key) {
                if (this.guests[key] > 0) {
                    this.guests[key]--;
                    $wire.set(key + '_count', this.guests[key]);
                }
            },

            calculatePrice() {
                if (this.nights === 0) return;
                let nightlySum = 0;
                const p = this.cabinRules.prices;
                nightlySum += this.guests.graduate * p.graduate;
                nightlySum += this.guests.student * p.student;
                nightlySum += this.guests.child * p.child;
                nightlySum += this.guests.external * p.external;
                if (p.dog) nightlySum += this.guests.dog * p.dog;
                if (p.wood) nightlySum += p.wood;
                this.totalPrice = nightlySum * this.nights;
            },

            formatPrice(val) {
                return new Intl.NumberFormat('cs-CZ', { style: 'currency', currency: 'CZK', maximumFractionDigits: 0 }).format(val);
            }
        }));
    </script>
    @endscript
</div>