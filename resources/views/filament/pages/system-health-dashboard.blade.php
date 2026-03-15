<x-filament::page>
    <div class="space-y-6">
        <!-- Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Database Connection -->
            <div
                class="p-6 bg-white dark:bg-slate-950 rounded-xl shadow-sm border-2 {{ $this->databaseConnectionStatus ? 'border-green-300 dark:border-green-700' : 'border-red-300 dark:border-red-700' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Databáze</div>
                        <div class="text-lg font-bold {{ $this->databaseConnectionStatus ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}"
                            style="color: {{ $this->databaseConnectionStatus ? '#16a34a' : '#dc2626' }}">
                            {{ $this->databaseConnectionStatus ? '✅ Připojeno' : '❌ Odpojeno' }}
                        </div>
                    </div>
                    <div class="text-3xl">{{ $this->databaseConnectionStatus ? '🟢' : '🔴' }}</div>
                </div>
            </div>

            <!-- Backup Status -->
            <div class="p-6 bg-white dark:bg-slate-950 rounded-xl shadow-sm border-2 border-green-300 dark:border-green-700"
                style="background-color: #fff">
                <div class="text-sm font-medium mb-2" style="color: #666">Poslední záloha</div>
                @php
                    $backup = $this->lastBackupInfo;
                @endphp
                @if ($backup)
                    <div class="text-sm">
                        <div class="font-bold mb-1" style="color: #16a34a">✅ Existuje</div>
                        <div class="text-xs" style="color: #666">
                            {{ \Carbon\Carbon::createFromTimestamp($backup['last_modified'])->diffForHumans() }}
                        </div>
                    </div>
                @else
                    <div class="text-sm font-bold" style="color: #dc2626">
                        ⚠️ Nebyla nalezena
                    </div>
                @endif
            </div>
        </div>

        <!-- Queue Status -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="p-4 bg-blue-50 dark:bg-blue-950 rounded-xl border border-blue-200 dark:border-blue-800"
                style="background-color: #eff6ff">
                <div class="text-sm font-medium mb-1" style="color: #1e40af">Čekající úlohy fronty</div>
                <div class="text-2xl font-bold" style="color: #1e40af">{{ $this->pendingJobsCount }}</div>
            </div>

            <div class="p-4 {{ $this->failedJobsCount > 0 ? 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800' : 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800' }} rounded-xl border"
                style="background-color: {{ $this->failedJobsCount > 0 ? '#fef2f2' : '#f0fdf4' }}">
                <div class="text-sm font-medium mb-1"
                    style="color: {{ $this->failedJobsCount > 0 ? '#991b1b' : '#15803d' }}">
                    Selhané úlohy
                </div>
                <div class="text-2xl font-bold" style="color: {{ $this->failedJobsCount > 0 ? '#991b1b' : '#15803d' }}">
                    {{ $this->failedJobsCount }}
                </div>
            </div>

            <div class="p-4 bg-purple-50 dark:bg-purple-950 rounded-xl border border-purple-200 dark:border-purple-800"
                style="background-color: #faf5ff">
                <div class="text-sm font-medium mb-1" style="color: #6d28d9">Úložiště (storage)</div>
                @php
                    $storage = $this->storageUsage;
                @endphp
                @if ($storage)
                    <div class="text-2xl font-bold" style="color: #6d28d9">
                        {{ round($storage['used'] / 1024 / 1024, 1) }} MB
                    </div>
                    <div class="text-xs mt-1" style="color: #6d28d9">{{ $storage['used_percent'] }}% z
                        dostupného
                    </div>
                @else
                    <div class="text-sm" style="color: #666">Nelze určit</div>
                @endif
            </div>
        </div>

        <!-- Booking Statistics -->
        <div
            class="mb-6 p-6 bg-white dark:bg-slate-950 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-bold mb-4" style="color: #000">📊 Statistiky rezervací</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @php
                    $stats = $this->bookingStats;
                @endphp
                <div class="text-center p-3 bg-gray-50 dark:bg-slate-900 rounded-lg" style="background-color: #f9fafb">
                    <div class="text-2xl font-bold" style="color: #111">{{ $stats['total'] }}</div>
                    <div class="text-xs" style="color: #666">Celkem</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-950 rounded-lg"
                    style="background-color: #fefce8">
                    <div class="text-2xl font-bold" style="color: #713f12">{{ $stats['pending'] }}</div>
                    <div class="text-xs" style="color: #713f12">Čekající</div>
                </div>
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-950 rounded-lg" style="background-color: #eff6ff">
                    <div class="text-2xl font-bold" style="color: #1e40af">{{ $stats['deposit_paid'] }}</div>
                    <div class="text-xs" style="color: #1e40af">Záloha zaplacena</div>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-950 rounded-lg" style="background-color: #f0fdf4">
                    <div class="text-2xl font-bold" style="color: #15803d">{{ $stats['paid'] }}</div>
                    <div class="text-xs" style="color: #15803d">Zaplaceno</div>
                </div>
                <div class="text-center p-3 bg-red-50 dark:bg-red-950 rounded-lg" style="background-color: #fef2f2">
                    <div class="text-2xl font-bold" style="color: #991b1b">{{ $stats['total'] - $stats['active'] }}
                    </div>
                    <div class="text-xs" style="color: #991b1b">Zrušeno</div>
                </div>
            </div>
        </div>

        <!-- Pending Bookings & Payment Risk -->
        @php
            $pendingStats = $this->pendingBookings;
        @endphp
        @if ($pendingStats['total'] > 0)
            <div
                class="mb-6 p-6 {{ $pendingStats['at_risk'] > 0 ? 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800' : 'bg-yellow-50 dark:bg-yellow-950 border-yellow-200 dark:border-yellow-800' }} rounded-xl border">
                <h3
                    class="text-lg font-semibold {{ $pendingStats['at_risk'] > 0 ? 'text-red-900 dark:text-red-200' : 'text-yellow-900 dark:text-yellow-200' }} mb-2">
                    ⚠️ Čekající rezervace bez platby
                </h3>
                <p
                    class="text-sm {{ $pendingStats['at_risk'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-yellow-700 dark:text-yellow-300' }}">
                    Celkem <strong>{{ $pendingStats['total'] }}</strong> rezervací čeká na platbu.
                    @if ($pendingStats['at_risk'] > 0)
                        <strong>{{ $pendingStats['at_risk'] }}</strong> z nich má rizikem vypršení lhůty (> 2 dny).
                    @endif
                </p>
            </div>
        @endif

        <!-- Fio Bank API Status -->
        @php
            $fioBankStatus = $this->fioBankStatus;
        @endphp
        <div class="mb-6 p-6 bg-white dark:bg-slate-950 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700"
            style="background-color: #fff">
            <h3 class="text-lg font-bold mb-4" style="color: #000">💳 Automatické zpracování plateb (Fio Bank
                API)</h3>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-white dark:bg-slate-900 {{ $fioBankStatus['configured'] ? 'border-2 border-green-300 dark:border-green-700' : 'border-2 border-yellow-300 dark:border-yellow-700' }} rounded-lg"
                    style="background-color: #fff">
                    <div class="text-sm font-bold"
                        style="color: {{ $fioBankStatus['configured'] ? '#16a34a' : '#ca8a04' }}">
                        {{ $fioBankStatus['configured'] ? '✅ Nakonfigurováno' : '⚠️ Není nastaveno' }}
                    </div>
                    @if ($fioBankStatus['configured'])
                        <div class="text-xs text-green-700 dark:text-green-300 mt-2" style="color: #16a34a">Token je
                            nastaven v .env</div>
                    @else
                        <div class="text-xs text-yellow-700 dark:text-yellow-300 mt-2" style="color: #ca8a04">P\u0159idejte
                            FIO_BANK_API_TOKEN do .env</div>
                    @endif
                </div>

                @if ($fioBankStatus['configured'])
                    <div class="p-4 bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800 rounded-lg border"
                        style="background-color: #eff6ff">
                        <div class="text-sm font-medium" style="color: #1e40af">Poslední běh</div>
                        @if ($fioBankStatus['last_transaction_at'])
                            <div class="text-xs mt-1" style="color: #1e40af">
                                {{ $fioBankStatus['last_transaction_at']->diffForHumans() }}
                            </div>
                        @else
                            <div class="text-xs mt-1" style="color: #1e40af">Ještě neběžel</div>
                        @endif
                    </div>

                    <div class="p-4 bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800 rounded-lg border"
                        style="background-color: #faf5ff">
                        <div class="text-sm font-medium" style="color: #6d28d9">Poslední 24 hodin</div>
                        <div class="text-2xl font-bold mt-1" style="color: #6d28d9">
                            {{ $fioBankStatus['matched_24h'] }}/{{ $fioBankStatus['transactions_24h'] }}
                        </div>
                        <div class="text-xs" style="color: #6d28d9">Spárováno / Celkem</div>
                    </div>

                    <div class="p-4 bg-indigo-50 dark:bg-indigo-950 border-indigo-200 dark:border-indigo-800 rounded-lg border"
                        style="background-color: #eef2ff">
                        <div class="text-sm font-medium" style="color: #3730a3">Příští běh</div>
                        <div class="text-lg font-semibold mt-1" style="color: #3730a3">
                            {{ $fioBankStatus['next_run'] ?? 'Mimo pracovní dobu' }}
                        </div>
                        <div class="text-xs" style="color: #3730a3">Plán: Každých 10 minut (7:00 - 22:00)</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Errors -->
        @php
            $errors = $this->recentErrors;
        @endphp
        @if (count($errors) > 0)
            <div class="mb-6 p-6 bg-red-50 dark:bg-red-950 rounded-xl shadow-sm border border-red-200 dark:border-red-800">
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-200 mb-4">🔴 Nedávné chyby</h3>
                <div class="space-y-2">
                    @foreach ($errors as $error)
                        <div
                            class="text-xs font-mono text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 p-2 rounded break-all max-h-20 overflow-y-auto">
                            {{ $error }}
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div
                class="mb-6 p-6 bg-green-50 dark:bg-green-950 rounded-xl shadow-sm border border-green-200 dark:border-green-800">
                <h3 class="text-lg font-semibold text-green-900 dark:text-green-200">✅ Žádné nedávné chyby</h3>
                <p class="text-sm text-green-700 dark:text-green-300 mt-2">Systém běží bez problémů.</p>
            </div>
        @endif

        <!-- Footer Notes -->
        <div class="p-4 bg-blue-50 dark:bg-blue-950 rounded-xl shadow-sm border border-blue-200 dark:border-blue-800">
            <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">ℹ️ Poznámky</h4>
            <ul class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                <li>• Tato stránka je dostupná pouze super administrátorům.</li>
                <li>• Pro detailní logy a diagnostiku využijte Docker: <code
                        class="bg-blue-100 dark:bg-blue-900 px-1 rounded">docker compose logs scheduler</code> nebo
                    <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">docker compose logs queue</code>
                </li>
                <li>• Fio Bank API běží každých 10 minut mezi 7:00 a 22:00.</li>
                <li>• Čekající úlohy by měly být zpracovány do několika minut. Selhané úlohy vyžadují okamžitou
                    pozornost.
                </li>
            </ul>
        </div>
    </div>
</x-filament::page>