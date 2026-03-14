<x-filament-panels::page>
    <x-filament::card>

        <div class="prose max-w-none dark:prose-invert">

            <h2>1. Jak fungují e-maily</h2>
            <p>Systém posílá e-maily automaticky podle toho, jestli má rezervace vyplněný e-mail a jaké přepínače jsou
                v rezervaci zapnuté.</p>

            <ul>
                <li><strong>Rezervace bez e-mailu:</strong> Pokud u rezervace není vyplněný e-mail zákazníka, systém:
                    <ul>
                        <li>vypne a zamkne přepínače pro odesílání e-mailů a hlídání plateb,</li>
                        <li>takovou rezervaci dál nesleduje (žádné e-maily, žádné upomínky).</li>
                    </ul>
                </li>
                <li><strong>Přepínač „Odeslat e-mail při uložení/úpravě“:</strong> Pokud je zapnutý a rezervace má
                    e-mail:
                    <ul>
                        <li>při změně termínu přijde zákazníkovi e-mail se změnou,</li>
                        <li>při částečné platbě dostane e-mail s informací o doplatku,</li>
                        <li>při zaplacení zálohy dostane potvrzení o přijetí platby.</li>
                    </ul>
                </li>
                <li><strong>Ukončené rezervace (historie):</strong> Pokud je termín konce rezervace již v minulosti,
                    systém při ukládání automaticky zcela vypne zaškrtávátka pro zasílání e-mailů a vymáhání plateb
                    ("natvrdo" na úrovni databáze). <strong>Zabraňuje se tak odeslání e-mailů a notifikací</strong> při
                    úpravách starých historických pobytů.</li>
            </ul>

            <hr />

            <h2>2. Jak systém hlídá platby</h2>
            <p>Každý den proběhne kontrola všech nezaplacených rezervací (stav <strong>Nezaplaceno / pending</strong>)
                podle lhůty splatnosti nastavené v Obecném nastavení.</p>

            <ul>
                <li><strong>Kdy se rezervace nehlídá:</strong>
                    <ul>
                        <li>chybí e-mail zákazníka, nebo</li>
                        <li>celková cena je 0 Kč.</li>
                    </ul>
                </li>
                <li><strong>Stav „Záloha zaplacena“:</strong>
                    <ul>
                        <li>rezervace je z pohledu systému v pořádku,</li>
                        <li>přestanou chodit upomínky,</li>
                        <li>zbytek ceny se doplácí hotově při příjezdu.</li>
                    </ul>
                </li>
                <li><strong>Přepínač „Vymáhat automatické termíny plateb“:</strong>
                    <ul>
                        <li>funguje jen pokud je stav <strong>Nezaplaceno</strong> a je vyplněný e-mail,</li>
                        <li>systém pošle upomínku 3 dny a 1 den před koncem lhůty.</li>
                    </ul>
                </li>
                <li><strong>Automatické zrušení rezervace:</strong>
                    <ul>
                        <li>pokud lhůta vyprší a stav je stále <strong>Nezaplaceno</strong>,</li>
                        <li>systém změní stav na <strong>Zrušeno</strong> a pošle zákazníkovi e-mail.</li>
                    </ul>
                </li>
            </ul>

            <hr />

            <h2>3. Překrývání termínů a „celá chata“</h2>
            <p>Systém umí hlídat kapacitu lůžek, ale zároveň umožňuje rezervovat celou chatu jen pro jednu skupinu.</p>

            <ul>
                <li><strong>Rezervovat celou chatu:</strong>
                    <ul>
                        <li>pokud je políčko zaškrtnuté, považuje se chata za plně obsazenou,</li>
                        <li>veřejný formulář na webu už na tyto dny nepustí další rezervace.</li>
                    </ul>
                </li>
                <li><strong>Řízené překrytí z administrace:</strong>
                    <ul>
                        <li>v administraci může správce v případě potřeby vytvořit překrývající se rezervace
                            (například údržba + host),</li>
                        <li>tento scénář veřejný formulář nikdy neumožní, je jen pro ruční zásahy správce.</li>
                    </ul>
                </li>
            </ul>

            <hr />

            <h2>4. Finance a ceny</h2>
            <p>Ceny, které systém navrhuje, vychází z Obecného nastavení. Vždy ale platí hodnota v poli
                <strong>Celková cena</strong> – ta má přednost.
            </p>

            <ul>
                <li><strong>Variabilní symbol:</strong>
                    <ul>
                        <li>pokud pole necháte prázdné, systém při uložení rezervace automaticky vytvoří unikátní
                            variabilní symbol,</li>
                        <li>usnadňuje to párování plateb z banky.</li>
                    </ul>
                </li>
                <li><strong>Záloha a „Přijatá částka“:</strong>
                    <ul>
                        <li>výše požadované zálohy se počítá podle procenta v Obecném nastavení,</li>
                        <li>skutečně přijaté peníze zapisujte do pole <strong>Přijatá částka</strong>,</li>
                        <li>jakmile tato částka dosáhne požadovaného minima, stav se automaticky změní na
                            <strong>Záloha zaplacena</strong>.
                        </li>
                    </ul>
                </li>
                <li><strong>Částečná platba (nedoplatek):</strong>
                    <ul>
                        <li>pokud je v poli <strong>Přijatá částka</strong> méně než potřebná záloha,</li>
                        <li>stav zůstává <strong>Nezaplaceno</strong>,</li>
                        <li>zákazník dostane e-mail s přesnou výší nedoplatku.</li>
                    </ul>
                </li>
                <li><strong>Nulová cena (0 Kč):</strong>
                    <ul>
                        <li>pokud nastavíte celkovou cenu na 0 Kč a opustíte pole,</li>
                        <li>systém zobrazí varování,</li>
                        <li>stav se přepne na <strong>Záloha zaplacena</strong> a hlídání plateb se vypne.</li>
                    </ul>
                </li>
            </ul>

        </div>

    </x-filament::card>
</x-filament-panels::page>