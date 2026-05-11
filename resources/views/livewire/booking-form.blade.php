<div x-data="bookingManager()" x-init="(async () => await init())()" class="w-full"> 
       <form wire:submit="submitReservation" class="space-y-8">
        @error('throttle')
            <div class="p-4 text-sm text-fg-danger-strong dark:text-red-400 bg-danger/10 dark:bg-red-900/20 border border-danger/30 dark:border-red-900/40 rounded-base">
                {{ $message }} -- Throttle error --
            </div>
        @enderror

        <div x-show="apiError" class="p-4 text-sm text-fg-danger-strong dark:text-red-400 bg-danger/10 dark:bg-red-900/20 border border-danger/30 dark:border-red-900/40 rounded-base">
            Nepodařilo se načíst dostupnost z API. Obnovte prosím stránku nebo zkuste akci za chvíli znovu.
        </div>
        
        <!-- 1. Ceník -->
        <section x-show="cabinRules.prices" class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Orientační ceník</h2>
            
            <!-- Grid container for simple text rows -->
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-8 text-sm text-body dark:text-gray-400">
                <template x-for="(label, key) in guestLabelsSingular" :key="key">
                    <!-- Standard pricing row -->
                    <li class="flex justify-between items-center" x-show="cabinRules.prices[key] !== undefined">
                        <span x-text="label"></span>
                        <span class="font-medium text-heading dark:text-gray-300" x-text="formatPrice(cabinRules.prices[key]) + ' / noc'"></span>
                    </li>
                </template>
                
                <!-- Wood fee -->
                <li class="flex justify-between items-center" x-show="cabinRules.prices.wood">
                    <span>Dřevo</span>
                    <span class="font-medium text-heading dark:text-gray-300" x-text="formatPrice(cabinRules.prices.wood) + ' / noc'"></span>
                </li>
            </ul>
             <span class="block mt-4 text-xs text-body dark:text-gray-400">Ceny jsou orientační, přesná částka bude uvedena v potvrzení rezervace.</span>   
        </section>

        <!-- 2. Payment Rules & Info -->
         <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Informace</h2>
            
            <div class="space-y-4 text-sm text-body dark:text-white">
                
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Rezervace přijímáme maximálně na jeden rok dopředu.</span>
                </div>

                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Maximální ubytovací kapacita chaty je omezena<span x-show="cabinRules.bed_capacity !== undefined"> na <strong x-text="cabinRules.bed_capacity"></strong> osob</span>.</span>
                </div>

                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Splatnost rezervace je <strong x-text="cabinRules.pending_window + ' dní'"></strong>. Prosíme o včasnou úhradu zálohy<span x-show="cabinRules.deposit_percentage"> ve výši <strong x-text="cabinRules.deposit_percentage + '%'"></strong> z celkové částky</span>, v opačném případě bude rezervace automaticky stornována.</span>
                </div>

                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 text-brand dark:text-blue-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                    </svg>
                    <span>Po odeslání rezervace Vám zašleme potvrdzovací e-mail s přesnými platebními údaji.</span>
                </div>
                
                
            </div>
        </section>

        <!-- 3. Booking Information -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
        <section>
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Informace o ubytování</h2>

           <div wire:ignore.self>
                <div wire:ignore>
                <h2 class="text-md mb-5 font-medium text-heading dark:text-white">Příjezd a odjezd</h2>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-body dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01M4 4h16a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <input x-ref="datepicker" type="text" readonly
                            class="block w-full ps-9 pe-3 py-2.5 bg-neutral-secondary-medium dark:bg-gray-700 border border-default-medium dark:border-gray-600 text-heading dark:text-white text-sm rounded-base focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 shadow-xs placeholder:text-body dark:placeholder-gray-400 cursor-pointer transition-colors"
                            placeholder="Vyberte termín...">
                    </div>
                </div>
            </div>

            @error('start_date') 
                <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> {{ $message }}</p> 
            @enderror
        
            <h2 class="text-md mt-5 font-medium text-heading dark:text-white">Hosté</h2>

            <div x-show="nights > 0" x-transition class="mt-0.5 mb-4 text-sm">
                <span class="text-body dark:text-gray-400">Dostupná kapacita pro tento termín:
                <span class="font-medium" :class="availableBeds > 0 ? 'text-fg-success-strong dark:text-green-400' : 'text-fg-danger-strong dark:text-red-400'"
                    x-text="luzkaText(availableBeds)"></span>.</span>
            </div>

            <!-- Guest count controls -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5">
                <template x-for="(label, key) in guestLabels" :key="key">
                    <div class="flex items-center justify-between p-3 bg-neutral-secondary-medium dark:bg-gray-700/50 border border-default-medium dark:border-gray-600 rounded-base transition-colors">
                        <div>
                            <span class="block text-sm font-medium text-heading dark:text-white" x-text="label"></span>
                            <span x-show="key === 'child'" class="block mt-0.5 text-xs text-body dark:text-gray-400">Do 15 let</span>                    

                        </div>
                        <div class="flex items-center">
                            <button type="button" @click="decrement(key)"
                                class="inline-flex items-center justify-center h-8 w-8 text-heading dark:text-gray-300 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-600 hover:bg-neutral-secondary-medium dark:hover:bg-gray-700 font-medium rounded-base transition-all disabled:opacity-50 disabled:cursor-not-allowed" :disabled="guests[key] <= 0">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                </svg>
                            </button>

                            <input type="number" x-model.number="guests[key]"
                                @change="guests[key] = Math.max(0, parseInt(guests[key]) || 0);
                                            if (key !== 'dog' && totalGuests > availableBeds) {
                                                guests[key] -= (totalGuests - availableBeds);
                                            }
                                            $wire.set(key + '_count', guests[key]);"
                                class="mx-2 w-8 h-6 leading-6 text-center text-sm font-medium text-heading dark:text-white bg-white dark:bg-gray-800 rounded border-0 focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />

                            <button type="button" @click="increment(key)"
                                class="inline-flex items-center justify-center h-8 w-8 text-heading dark:text-gray-300 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-600 hover:bg-neutral-secondary-medium dark:hover:bg-gray-700 font-medium rounded-base transition-all disabled:opacity-50 disabled:cursor-not-allowed" :disabled="totalGuests >= availableBeds && key !== 'dog'">
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

            <!-- 3. Contact Information -->
            <h2 class="mb-5 mt-12 text-lg font-medium text-heading dark:text-white">Kontaktní údaje</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                
                <div>
                    <label for="customer_name" class="block mb-2.5 text-sm font-medium @error('customer_name') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">Celé jméno</label>
                    <input type="text" id="customer_name" wire:model="customer_name"
                        class="block w-full px-3 py-2.5 text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 [&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#F9FAFB] [&:-webkit-autofill]:[-webkit-text-fill-color:#111827] dark:[&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#374151] dark:[&:-webkit-autofill]:[-webkit-text-fill-color:#F9FAFB] @error('customer_name') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                        placeholder="Vaše jméno">
                    @error('customer_name') 
                        <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> {{ $message }}</p> 
                    @enderror
                </div>

                <div>
                    <label for="customer_email" class="block mb-2.5 text-sm font-medium @error('customer_email') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">E-mail</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 @error('customer_email') text-fg-danger-strong dark:text-red-400 @else text-body dark:text-gray-400 @enderror" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                            </svg>
                        </div>
                        <input type="email" id="customer_email" wire:model="customer_email"
                            class="block w-full ps-9 pe-3 py-2.5 text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 [&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#F9FAFB] [&:-webkit-autofill]:[-webkit-text-fill-color:#111827] dark:[&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#374151] dark:[&:-webkit-autofill]:[-webkit-text-fill-color:#F9FAFB] @error('customer_email') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                            placeholder="vas@email.cz">
                    </div>
                    @error('customer_email') 
                        <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> {{ $message }}</p> 
                    @enderror
                </div>

                <div>
                    <label for="customer_phone" class="block mb-2.5 text-sm font-medium @error('customer_phone') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">Telefon</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 @error('customer_phone') text-fg-danger-strong dark:text-red-400 @else text-body dark:text-gray-400 @enderror" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.427 14.768 17.2 13.542a1.733 1.733 0 0 0-2.45 0l-.613.613a1.732 1.732 0 0 1-2.45 0l-1.838-1.84a1.735 1.735 0 0 1 0-2.452l.612-.613a1.735 1.735 0 0 0 0-2.452L9.237 5.572a1.6 1.6 0 0 0-2.45 0c-3.223 3.2-1.453 7.44 1.086 10.049 2.567 2.566 6.847 4.34 10.554 1.152a1.602 1.602 0 0 0 0-2.005Z"/>
                            </svg>
                        </div>
                        <input type="tel" id="customer_phone" wire:model="customer_phone"
                            class="block w-full ps-9 pe-3 py-2.5 text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 [&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#F9FAFB] [&:-webkit-autofill]:[-webkit-text-fill-color:#111827] dark:[&:-webkit-autofill]:shadow-[inset_0_0_0px_1000px_#374151] dark:[&:-webkit-autofill]:[-webkit-text-fill-color:#F9FAFB] @error('customer_phone') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                            placeholder="123 456 789">
                    </div>
                    @error('customer_phone') 
                        <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> {{ $message }}</p> 
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="customer_notes" class="block mb-2.5 text-sm font-medium @error('customer_notes') text-fg-danger-strong dark:text-red-400 @else text-heading dark:text-white @enderror">Poznámka k rezervaci</label>
                    <textarea id="customer_notes" wire:model="customer_notes" rows="4" 
                        class="block w-full p-3.5 min-h-[120px] text-sm rounded-base shadow-xs transition-colors bg-neutral-secondary-medium dark:bg-gray-700 placeholder:text-body dark:placeholder-gray-400 @error('customer_notes') border border-danger dark:border-red-500 text-fg-danger-strong dark:text-red-400 focus:ring-danger focus:border-danger dark:focus:ring-red-500 dark:focus:border-red-500 @else border border-default-medium dark:border-gray-600 text-heading dark:text-white focus:ring-brand focus:border-brand dark:focus:ring-blue-500 dark:focus:border-blue-500 @enderror" 
                        placeholder="Napište případné doplňující informace..."></textarea>
                    @error('customer_notes') 
                        <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> {{ $message }}</p> 
                    @enderror
                </div>
                
            </div>
        </section>
        </div>

        <!-- 6. Final Summary & Submit -->
        <section class="p-6 bg-white dark:bg-gray-800 border border-default-medium dark:border-gray-700 rounded-base shadow-xs transition-colors">
            <h2 class="mb-5 text-lg font-medium text-heading dark:text-white">Shrnutí a odeslání rezervace</h2>
            
            <!-- Summary -->
            <dl class="space-y-3 mb-6 bg-neutral-secondary-medium dark:bg-gray-700/50 p-4 rounded-base border border-default-medium dark:border-gray-600">
                <div class="flex justify-between text-sm">
                    <dt class="text-body dark:text-white">Počet nocí:</dt>
                    <dd class="font-medium text-heading dark:text-white" x-text="nights"></dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-body dark:text-white">Počet hostů:</dt>
                    <dd class="font-medium text-heading dark:text-white" x-text="totalGuests"></dd>
                </div>
                <div class="pt-3 border-t border-default-medium dark:border-gray-600 flex justify-between items-center">
                    <dt class="text-base font-medium text-heading dark:text-white">Předběžná celková cena:</dt>
                    <dd class="text-xl font-medium text-brand dark:text-blue-400" x-text="formatPrice(totalPrice)"></dd>
                </div>
            </dl>

            <!-- Consent -->
            <div class="space-y-4">
                <div class="flex items-start mt-4">
                     <input wire:model="consent_acknowledgement" id="consent_acknowledgement"
                     type="checkbox" class="flex-shrink-0 mt-1 w-4 h-4 border border-default-medium dark:border-gray-600 rounded-xs bg-neutral-secondary-medium dark:bg-gray-700 focus:ring-2 focus:ring-brand-soft dark:focus:ring-blue-600 dark:ring-offset-gray-800 cursor-pointer transition-colors">
                    <label for="consent_acknowledgement" class="ms-3 mt-0 text-sm font-medium text-heading dark:text-gray-300 select-none cursor-pointer leading-relaxed">
                        Potvrzuji, že jsem si vědom skutečnosti, že chata Děvín není komerčně používaným objektem a slouží výhradně pro účely studentů a zaměstnanců Gymnázia Trutnov, případně pro absolventy Gymnázia Trutnov a osoby, které se významně zasloužili o její údržbu či o správu Nadačního fondu Gymnázia Trutnov a jeho činnost. Nadační fond Gymnázia Trutnov spravuje chatu Děvín z darů, které obdrží od svých dárců, mezi něž patří i osoby uvedené v první větě, které chatu mohou příležitostně užívat. Rezervační formulář slouží pro potřeby Gymnázia Trutnov, Nadačního fondu Gymnázia Trutnov a výše uvedených osob, aby uvedení mohli sledovat, kým je chata kdy užívána, ať už za účelem prosté návštěvy, nebo pro rekonstrukční práce či zásobování (zejména topným dřevem). Výslovně tímto potvrzuji, že patřím mezi studenty, zaměstnance nebo absolventy Gymnázia Trutnov a že všechny uvedené informace ve formuláři jsou pravdivé.
                    </label>
                </div>
                @error('consent_acknowledgement') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> Prosím potvrdit souhlas.</p> 
                @enderror


                <div class="flex items-start mt-4">
                    <input wire:model="consent_privacy" id="consent_privacy" type="checkbox" class="flex-shrink-0 mt-1 w-4 h-4 border border-default-medium dark:border-gray-600 rounded-xs bg-neutral-secondary-medium dark:bg-gray-700 focus:ring-2 focus:ring-brand-soft dark:focus:ring-blue-600 dark:ring-offset-gray-800 cursor-pointer transition-colors">
                    <label for="consent_privacy" class="ms-3 mt-0 text-sm font-medium text-heading dark:text-gray-300 select-none cursor-pointer leading-relaxed">
                        Souhlasím se zpracováním osobních údajů za účelem zpracování rezervace. Výslovně uděluji souhlas s tím, aby moje jméno bylo zveřejněno na webových stránkách v kalendáři dostupnosti.
                    </label>
                </div>
                @error('consent_privacy') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> Prosím potvrďte souhlas se zpracováním osobních údajů.</p> 
                @enderror


                <div class="flex items-start mt-4">
                     <input wire:model="consent_house_rules" id="consent_house_rules"
                     type="checkbox" class="flex-shrink-0 mt-1 w-4 h-4 border border-default-medium dark:border-gray-600 rounded-xs bg-neutral-secondary-medium dark:bg-gray-700 focus:ring-2 focus:ring-brand-soft dark:focus:ring-blue-600 dark:ring-offset-gray-800 cursor-pointer transition-colors">
                    <label for="consent_house_rules" class="ms-3 mt-0 text-sm font-medium text-heading dark:text-gray-300 select-none cursor-pointer leading-relaxed">
                        Potvrzuji, že si pečlivě přečtu <a href="#" class="text-fg-brand dark:text-blue-400 hover:underline">podmínky ubytování</a> a seznámím s nimi všechny ostatní účastníky pobytu. Zároveň se zavazují dodržovat tyto podmínky a beru odpovědnost za jejich dodržování.
                    </label>
                </div>
                @error('consent_house_rules') 
                    <p class="mt-2.5 text-sm text-fg-danger-strong dark:text-red-400"><span class="font-medium"></span> Prosím potvrďte, že se zavazujete dodržovat podmínky ubytování.</p> 
                @enderror

        
                <button type="submit" wire:loading.attr="disabled"
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
            apiError: null,
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
                    if (!response.ok) {
                        throw response;
                    }
                    const data = await response.json();
                    this.cabinRules = data.cabin_rules || {};
                    this.bookings = data.bookings || [];
                    this.availableBeds = this.cabinRules.bed_capacity || 0;
                    this.apiError = false;
                } catch (error) {
                    if (error?.status === 429) {
                        console.warn('Availability API throttled (429).');
                    } else {
                        console.error('Error fetching availability:', error);
                    }
                    this.apiError = true;
                } finally {
                    this.initPicker();
                    this.$watch('guests', () => this.calculatePrice(), { deep: true });
                }
            },

           initPicker() {
                const maxAllowedDate = new Date();
                maxAllowedDate.setFullYear(maxAllowedDate.getFullYear() + 1);

                // Flatpickr bere pole objektů s daty od-do pro zablokování
                let lockedDays = [];
                this.bookings.forEach(b => {
                    if (b.reserve_whole) {
                        lockedDays.push({
                            from: b.start_date,
                            to: b.end_date
                        });
                    }
                });

                // Inicializace Flatpickr
                flatpickr(this.$refs.datepicker, {
                    mode: 'range',
                    dateFormat: 'd.m.Y',
                    locale: 'cs',
                    minDate: 'today',
                    maxDate: maxAllowedDate,
                    disable: [],
                    onChange: (selectedDates) => {
                        // Kód se spustí až když uživatel klikne na druhé datum (konec pobytu)
                        if (selectedDates.length === 2) {
                            const start = this.formatDate(selectedDates[0]);
                            const end = this.formatDate(selectedDates[1]);
                            
                            this.nights = Math.ceil((selectedDates[1].getTime() - selectedDates[0].getTime()) / (1000 * 60 * 60 * 24));
                            $wire.set('start_date', start);
                            $wire.set('end_date', end);

                            this.calculateAvailability(start, end);
                            this.calculatePrice();
                        }
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