<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-4 bg-white rounded-xl shadow-sm border">
            <div class="text-sm font-medium text-gray-500 mb-1">Čekající úlohy fronty</div>
            <div class="text-2xl font-bold text-gray-900">
                {{ $this->pendingJobsCount }}
            </div>
        </div>

        <div class="p-4 bg-white rounded-xl shadow-sm border">
            <div class="text-sm font-medium text-gray-500 mb-1">Selhané úlohy fronty</div>
            <div class="text-2xl font-bold {{ $this->failedJobsCount > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $this->failedJobsCount }}
            </div>
        </div>

        <div class="p-4 bg-white rounded-xl shadow-sm border">
            <div class="text-sm font-medium text-gray-500 mb-1">Poslední databázová záloha</div>
            @php
                $backup = $this->lastBackupInfo;
            @endphp
            @if ($backup)
                <div class="text-sm text-gray-900 break-all">
                    <div class="font-semibold">Soubor:</div>
                    <div>{{ $backup['path'] }}</div>
                    <div class="mt-2 text-xs text-gray-600">
                        Čas poslední změny:
                        {{ \Carbon\Carbon::createFromTimestamp($backup['last_modified'])->format('d.m.Y H:i') }}
                    </div>
                </div>
            @else
                <div class="text-sm text-red-600">
                    Nebyl nalezen žádný soubor zálohy v adresáři <code>backups/</code>.
                </div>
            @endif
        </div>
    </div>

    <div class="mt-8 p-4 bg-white rounded-xl shadow-sm border">
        <h2 class="text-lg font-semibold mb-2">Poznámka</h2>
        <p class="text-sm text-gray-700">
            Tato stránka je dostupná pouze super administrátorům a slouží k rychlé kontrole základních metrik systému.
            Pro detailní logy využijte standardní logovací soubory Laravelu na serveru.
        </p>
    </div>
</x-filament::page>

