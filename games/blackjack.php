<?php
$base = '../';                                    // sidan ligger i games/, så rotmappen är en nivå upp
$title = 'Blackjack';                             // namnet i webbläsarfliken
include __DIR__ . '/../includes/header.php';      // menyn, saldot och js/app.js
?>

<main>

  <!-- ===== SPELET (layout inspirerad av Stake: kontroller till vänster, bordet till höger) ===== -->
  <div class="bj-layout">                         <!-- spelet + live stats bredvid varandra -->

    <section class="bj-game">                     <!-- en panel: kontroller + bord -->

        <!-- KONTROLLER -->
        <div class="bj-controls">
            <label for="bet">Insats</label>
            <div class="bj-bet">                                          <!-- insats + snabbknappar på en rad -->
                <input type="number" id="bet" min="1" value="100">
                <button class="bj-small" id="btn-half">½</button>         <!-- halvera insatsen -->
                <button class="bj-small" id="btn-x2">2×</button>      <!-- dubbla insatsen -->
            </div>

            <div class="bj-actions">                                      <!-- 2 × 2 knappar för dragen -->
                <button class="bj-action" id="btn-hit" disabled>Hit</button>
                <button class="bj-action" id="btn-stand" disabled>Stand</button>
                <button class="bj-action" id="btn-double" disabled>Double</button>
                <button class="bj-action" id="btn-split" disabled>Split</button>
            </div>

            <button class="button bj-play" id="btn-deal">Spela</button>   <!-- stor knapp = ny hand -->

            <p id="message" class="message">Välj insats och tryck på Spela.</p>

            <!-- Hur ofta får man blackjack? Teorin säger 4,83 % (se informationen nedanför) -->
            <p class="stats" id="stats">Händer: 0 | Blackjack: 0</p>
        </div>

        <!-- BORDET -->
        <div class="bj-table">
            <div class="bj-shoe"></div>                                   <!-- kortleken uppe till höger -->

            <div class="bj-hand">                                         <!-- dealerns hand -->
                <span class="bj-total" id="dealer-total"></span>          <!-- dealerns summa -->
                <div class="bj-cards" id="dealer-cards"></div>            <!-- dealerns kort -->
            </div>

            <div class="bj-rules">                                        <!-- reglerna mitt på bordet, som på Stake -->
                <span>BLACKJACK BETALAR 3 TILL 2</span>
                <span>DEALERN STANNAR PÅ 17</span>
                <div class="bj-result" id="result"></div>                 <!-- +150 / −100 visas här -->
            </div>

            <!-- dina händer: en, eller två efter split (byggs av JavaScript) -->
            <div class="bj-hands" id="player-hands"></div>
        </div>
    </section>

    <!-- Live stats (byggs av js/stats.js) -->
    <aside class="live-stats" id="live-stats"></aside>
  </div>

  <!-- ===== INFORMATION: hur man spelar och chanserna ===== -->
  <section class="bj-info">

    <h2>Så fungerar Blackjack</h2>

    <div class="info-grid">

        <article class="info-card">
            <h3>Reglerna</h3>
            <ul>
                <li>Du och dealern får två kort var. Ett av dealerns kort ligger dolt.</li>
                <li>Kom så nära 21 som möjligt utan att gå över. Över 21 = <b>tjock</b> och du förlorar direkt.</li>
                <li><b>Hit</b> = ta ett kort till. <b>Stand</b> = stanna.</li>
                <li>Dealern har inga val: drar under 17 och stannar på 17 eller mer.</li>
                <li>Ny blandad lek (52 kort) varje hand.</li>
            </ul>
        </article>

        <article class="info-card">
            <h3>Kortens värde</h3>
            <ul>
                <li>2–10 är värda sin siffra.</li>
                <li>Knekt, dam och kung är värda 10.</li>
                <li>Ess är värt 11, eller 1 om summan annars blir över 21.</li>
                <li>En hand där esset räknas som 11 kallas <b>mjuk</b> (t.ex. ess + 6 = mjuk 17). Den kan inte bli tjock av ett kort till.</li>
            </ul>
        </article>

        <article class="info-card">
            <h3>Utbetalning (odds)</h3>
            <table>
                <tr><th>Utfall</th><th>Odds</th><th>Netto per 100 kr</th></tr>
                <tr><td>Blackjack (ess + tiokort)</td><td>3:2</td><td class="pos">+150</td></tr>
                <tr><td>Vinst</td><td>1:1</td><td class="pos">+100</td></tr>
                <tr><td>Vinst efter Double</td><td>1:1 på 200</td><td class="pos">+200</td></tr>
                <tr><td>Lika (push)</td><td>–</td><td>0</td></tr>
                <tr><td>Förlust eller tjock</td><td>–</td><td class="neg">−100</td></tr>
            </table>
            <p class="note">Efter Split räknas varje hand för sig. 21 efter split är inte blackjack och betalar 1:1.</p>
        </article>

        <article class="info-card wide">
            <h3>Double och Split</h3>
            <div class="info-cols">
                <div>
                    <p><b>Double</b> = dubbla insatsen, få <b>exakt ett</b> kort till och stanna. Går bara på de två första korten. Bra när du har 10 eller 11, då är chansen stor att nästa kort är ett tiokort.</p>
                </div>
                <div>
                    <p><b>Split</b> = har du två kort med samma värde (t.ex. 8 + 8) delas de till två händer. Den nya handen kostar en insats till. Varje hand får ett nytt kort och spelas för sig. Du får splitta en gång, och dubbla efter split. Delade ess får bara ett kort var.</p>
                </div>
            </div>
        </article>

    </div>

    <h2>Chanserna</h2>

    <div class="info-grid">

        <article class="info-card">
            <h3>Chansen att få blackjack</h3>
            <p>Du behöver ett ess (4 av 52 kort) och ett tiokort (16 av 51 kvar), i valfri ordning:</p>
            <p class="formula">P = 2 · 4/52 · 16/51 = 128/2652 ≈ <b>4,83 %</b></p>
            <p>Ungefär en gång på 21 händer. Rutan under knapparna visar hur ofta <i>du</i> har fått det, så du kan jämföra med teorin.</p>
        </article>

        <article class="info-card">
            <h3>Chansen att bli tjock om du tar ett kort</h3>
            <p>Med en hård hand: räkna de valörer som tar dig över 21. Tiokort finns 4 gånger (10, J, Q, K).</p>
            <table>
                <tr><th>Din summa</th><th>Kort som gör dig tjock</th><th>Chans</th></tr>
                <tr><td>12</td><td>10, J, Q, K</td><td>16/52 ≈ 31 %</td></tr>
                <tr><td>13</td><td>9 och uppåt</td><td>20/52 ≈ 38 %</td></tr>
                <tr><td>14</td><td>8 och uppåt</td><td>24/52 ≈ 46 %</td></tr>
                <tr><td>15</td><td>7 och uppåt</td><td>28/52 ≈ 54 %</td></tr>
                <tr><td>16</td><td>6 och uppåt</td><td>32/52 ≈ 62 %</td></tr>
                <tr><td>17</td><td>5 och uppåt</td><td>36/52 ≈ 69 %</td></tr>
            </table>
            <p class="note">Ungefärligt: korten som redan ligger på bordet saknas i leken.</p>
        </article>

        <article class="info-card">
            <h3>Hur ofta blir dealern tjock?</h3>
            <p>Beror på dealerns synliga kort. Simulerat med 5 000 000 händer:</p>
            <table>
                <tr><th>Dealern visar</th><th>Blir tjock</th></tr>
                <tr><td>2 / 3 / 4</td><td>35 % / 38 % / 40 %</td></tr>
                <tr><td>5 / 6</td><td class="pos">43 % / 42 %</td></tr>
                <tr><td>7 / 8 / 9</td><td>26 % / 24 % / 23 %</td></tr>
                <tr><td>10 / J / Q / K</td><td>21 %</td></tr>
                <tr><td>Ess</td><td class="neg">12 %</td></tr>
                <tr><td><b>Totalt</b></td><td><b>28 %</b></td></tr>
            </table>
            <p class="note">Därför stannar man ofta på 13–16 när dealern visar 2–6: dealern blir tjock ofta.</p>
        </article>

    </div>

  </section>
</main>

<script src="../js/stats.js"></script>

<script>
// ---- Inställningar ----
const CARD_DELAY = 350;     // ms mellan varje kort som delas ut
const ANIM_TIME = 450;      // ms som ett korts animation tar (samma som 0.45s i style.css)

const RANKS = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];   // valör = kort % 13
const SUITS = ['♠', '♥', '♦', '♣'];                                                  // färg  = kort / 13

// ---- Element på sidan ----
const dealerCards = document.getElementById('dealer-cards');    // raden med dealerns kort
const dealerTotal = document.getElementById('dealer-total');    // dealerns summa
const handsEl = document.getElementById('player-hands');        // plats för dina händer (1 eller 2)
const result = document.getElementById('result');               // +150 / −100 mitt på bordet
const message = document.getElementById('message');             // meddelandet under knapparna
const stats = document.getElementById('stats');                 // raden "Händer: ... | Blackjack: ..."
const betInput = document.getElementById('bet');                // rutan där man skriver insatsen
const btnDeal = document.getElementById('btn-deal');            // knappen Spela
const btnHit = document.getElementById('btn-hit');              // knappen Hit
const btnStand = document.getElementById('btn-stand');          // knappen Stand
const btnDouble = document.getElementById('btn-double');        // knappen Double
const btnSplit = document.getElementById('btn-split');          // knappen Split

const live = new LiveStats(document.getElementById('live-stats'), { key: 'blackjack' });   // panelen till höger

// ---- Variabler som ändras under spelet ----
let inHand = false;         // true medan en hand pågår
let canDouble = false;      // servern säger om Double är tillåtet just nu
let canSplit = false;       // servern säger om Split är tillåtet just nu
let step = 0;               // räknar korten i en uppdatering, så de kan komma ett i taget
let hands = 0;              // antal spelade omgångar
let blackjacks = 0;         // antal gånger spelaren fick blackjack

// ---- Knapparna ----
btnDeal.onclick = () => play({ action: 'deal', bet: parseInt(betInput.value) });   // ny hand med insatsen
btnHit.onclick = () => play({ action: 'hit' });                                    // ett kort till
btnStand.onclick = () => play({ action: 'stand' });                                // stanna
btnDouble.onclick = () => play({ action: 'double' });                              // dubbla
btnSplit.onclick = () => play({ action: 'split' });                                // dela paret

document.getElementById('btn-half').onclick = () => {                   // ½: halva insatsen, minst 1
    betInput.value = Math.max(1, Math.floor(betInput.value / 2));
};
document.getElementById('btn-x2').onclick = () => {                     // 2×: dubbla insatsen
    betInput.value = Math.max(1, betInput.value * 2);
};

// Slår på rätt knappar
function updateButtons() {
    btnDeal.disabled = inHand;                      // Spela går inte mitt i en hand
    btnHit.disabled = !inHand;                      // Hit och Stand går alltid mitt i en hand
    btnStand.disabled = !inHand;
    btnDouble.disabled = !inHand || !canDouble;     // Double bara när servern tillåter det
    btnSplit.disabled = !inHand || !canSplit;       // Split bara när servern tillåter det
}

// Stänger av alla knappar (medan vi väntar på servern), så man inte kan dubbelklicka
function lockButtons() {
    btnDeal.disabled = true;
    btnHit.disabled = true;
    btnStand.disabled = true;
    btnDouble.disabled = true;
    btnSplit.disabled = true;
}

// Visar ett felmeddelande i rött
function showError(text) {
    message.textContent = text;         // texten
    message.className = 'message lose'; // röd färg
    updateButtons();                    // låt spelaren fortsätta där den var
}

// Skickar ett drag till servern och returnerar svaret
async function send(body) {
    const res = await fetch(BASE + 'api/blackjack.php', {     // anropa API:t
        method: 'POST',                                       // spel skickas alltid med POST
        headers: { 'Content-Type': 'application/json' },      // vi skickar JSON
        body: JSON.stringify(body)                            // t.ex. {"action":"hit"}
    });
    const data = await res.json();                            // svaret som ett JS-objekt
    if (!res.ok) throw new Error(data.error);                 // servern sa nej, t.ex. "Ogiltig insats."
    return data;
}

// Gör om ett resultat till text: +100, −100 eller ±0
function fmtNet(n) {
    if (n > 0) return '+' + n;
    if (n < 0) return '−' + (-n);
    return '±0';
}

// Skapar ett kort. card = 0-51, eller null för baksidan (dealerns dolda kort)
function makeCard(card, animation) {
    const div = document.createElement('div');                 // nytt element
    div.className = 'card ' + animation;                       // 'deal' = glid in, 'flip' = vänd
    div.style.animationDelay = (step * CARD_DELAY) + 'ms';     // vänta på sin tur
    step++;                                                    // nästa kort väntar lite längre

    if (card === null) {                                       // dolt kort:
        div.classList.add('back');                             // visa baksidan
        return div;                                            // ingen text på baksidan
    }

    const rank = RANKS[card % 13];                             // t.ex. 'K'
    const suit = SUITS[Math.floor(card / 13)];                 // t.ex. '♥'
    if (suit === '♥' || suit === '♦') div.classList.add('red');   // hjärter och ruter är röda

    div.innerHTML = '<span>' + rank + suit + '</span>' +       // liten text i hörnet
                    '<span class="suit">' + suit + '</span>';  // stor symbol nere till höger
    return div;
}

// Lägger ett nytt kort sist i en rad
function addCard(row, card) {
    row.appendChild(makeCard(card, 'deal'));
}

// Skapar en tom plats för en spelarhand: kort, summa och insats
function makeHandBox() {
    const box = document.createElement('div');
    box.className = 'bj-hand';
    box.innerHTML = '<div class="bj-cards"></div>' +           // korten
                    '<span class="bj-total"></span>' +         // summan
                    '<span class="bj-handbet"></span>';        // insatsen på handen
    return box;
}

// Raden med kort för hand nummer i (0 eller 1)
function cardsOf(i) {
    return handsEl.children[i].querySelector('.bj-cards');
}

// Ritar bordet utifrån serverns svar och returnerar hur länge animationerna tar
function showTable(data, newHand) {
    step = 0;                                                  // börja räkna korten från noll

    if (newHand) {                                             // ny hand: töm bordet
        dealerCards.innerHTML = '';
        handsEl.innerHTML = '';
    }

    // Split: servern har en hand mer än vi visar. Flytta andra kortet till den nya handen.
    if (!newHand && data.hands.length > handsEl.children.length) {
        handsEl.appendChild(makeHandBox());                    // plats för hand 2
        const moved = cardsOf(0).lastElementChild;             // andra kortet i hand 1
        moved.classList.remove('deal');                        // ingen ny animation när det flyttas
        cardsOf(1).appendChild(moved);                         // flytta det
    }

    while (handsEl.children.length < data.hands.length) {      // ny hand (eller sidan laddades om)
        handsEl.appendChild(makeHandBox());
    }

    if (newHand) {                                             // dela ut i samma ordning som servern:
        addCard(cardsOf(0), data.hands[0].cards[0]);           // spelare,
        addCard(dealerCards, data.dealer[0]);                  // dealer,
        addCard(cardsOf(0), data.hands[0].cards[1]);           // spelare,
        addCard(dealerCards, data.done ? data.dealer[1] : null);   // dealer (dolt om handen pågår)
    }

    // Nya kort i varje hand: allt som servern har men som inte syns än
    data.hands.forEach((h, i) => {
        for (let j = cardsOf(i).children.length; j < h.cards.length; j++) {
            addCard(cardsOf(i), h.cards[j]);
        }
    });

    // Spelet är slut: byt baksidan mot det riktiga kortet, med vänd-animation
    const back = dealerCards.querySelector('.back');           // finns det ett dolt kort?
    if (data.done && back) {
        back.replaceWith(makeCard(data.dealer[1], 'flip'));
    }

    // Dealerns nya kort (när dealern drar)
    for (let i = dealerCards.children.length; i < data.dealer.length; i++) {
        addCard(dealerCards, data.dealer[i]);
    }

    return step * CARD_DELAY + ANIM_TIME;                      // sista kortets väntetid + dess animation
}

// Visar summor och insatser. Efter split tonas handen som inte spelas ner.
function showHands(data) {
    dealerTotal.textContent = data.dealerTotal;                // dealerns synliga summa
    dealerTotal.className = 'bj-total show';

    data.hands.forEach((h, i) => {
        const box = handsEl.children[i];                       // handens plats på bordet
        const total = box.querySelector('.bj-total');
        total.textContent = h.total;                           // summan
        total.className = 'bj-total show';                     // neutral färg
        box.querySelector('.bj-handbet').textContent = 'Insats ' + h.bet;   // 200 om man dubblat
        const waiting = !data.done && data.hands.length > 1 && i !== data.active;   // två händer och inte denna
        box.classList.toggle('dim', waiting);                  // tona ner handen som väntar
    });
}

// Text för en hands resultat
function handText(h, data) {
    if (h.result === 'blackjack') return 'Blackjack!';
    if (h.result === 'push') return 'Lika';
    if (h.result === 'bust') return 'Tjock';
    if (h.result === 'win') return data.dealerTotal > 21 ? 'Dealern tjock' : 'Vinst';
    return 'Förlust';
}

// Visar vinst/förlust när spelet är slut
function showResult(data) {
    const color = n => n > 0 ? 'win' : n < 0 ? 'lose' : 'push';   // grön, röd eller grå

    data.hands.forEach((h, i) => {                             // färga varje hands summa
        handsEl.children[i].querySelector('.bj-total').classList.add(color(h.net));
    });

    result.textContent = data.net === 0 ? 'Lika' : fmtNet(data.net);   // totalen mitt på bordet
    result.className = 'bj-result show ' + color(data.net);    // "poppar" upp

    const many = data.hands.length > 1;                        // splittade vi?
    message.textContent = data.hands.map((h, i) =>             // t.ex. "Hand 1: Vinst +100 · Hand 2: Tjock −100"
        (many ? 'Hand ' + (i + 1) + ': ' : '') + handText(h, data) + ' ' + fmtNet(h.net)
    ).join(' · ');
    message.className = 'message ' + (data.net === 0 ? '' : color(data.net));

    hands++;                                                   // en omgång till
    if (data.hands[0].result === 'blackjack') blackjacks++;    // räkna blackjacks
    const pct = (100 * blackjacks / hands).toFixed(1).replace('.', ',');   // andel i procent, svenskt komma
    stats.textContent = 'Händer: ' + hands + ' | Blackjack: ' + blackjacks + ' (' + pct + ' %, teori 4,83 %)';
}

// Körs när man trycker på en knapp
async function play(body) {
    const newHand = body.action === 'deal';                    // är det en ny hand?

    if (newHand && !(body.bet >= 1)) {                         // tom eller för liten insats
        showError('Ogiltig insats.');
        return;
    }

    lockButtons();                                             // inga dubbelklick
    message.textContent = '...';                               // visa att vi väntar
    message.className = 'message';

    let data;
    try {
        data = await send(body);                               // servern drar korten och räknar
    } catch (e) {
        // TypeError = servern svarade inte alls. Annars: serverns fel, t.ex. "Du har inte tillräckligt med saldo."
        showError(e instanceof TypeError ? 'Kunde inte nå servern. Kör sajten med: php -S localhost:8000' : e.message);
        return;
    }

    if (newHand) {                                             // ny hand: göm förra handens resultat
        dealerTotal.className = 'bj-total';
        result.className = 'bj-result';
    }

    const wait = showTable(data, newHand);                     // starta animationerna

    setTimeout(() => {                                         // vänta tills korten ligger på bordet
        showHands(data);                                       // visa summor och insatser
        inHand = !data.done;                                   // pågår spelet fortfarande?
        canDouble = data.canDouble;                            // vad får man göra nu?
        canSplit = data.canSplit;

        if (data.done) {                                       // spelet är slut:
            showResult(data);                                  // vinst eller förlust
            showBalance(data.balance);                         // nytt saldo i menyn (js/app.js)
            const totalBet = data.hands.reduce((sum, h) => sum + h.bet, 0);   // allt som låg på bordet
            live.add(totalBet, data.net);                      // uppdatera live stats
        } else if (data.hands.length > 1) {
            message.textContent = 'Hand ' + (data.active + 1) + ': vad gör du?';   // efter split
        } else {
            message.textContent = 'Vad gör du?';               // spelaren ska välja
        }

        updateButtons();                                       // rätt knappar igen
    }, wait);
}

// Om sidan laddas om mitt i en hand: hämta den från servern och fortsätt
async function resume() {
    try {
        const data = await send({ action: 'state' });          // finns det en pågående hand?
        if (!data.hands) return;                               // nej: gör inget

        betInput.value = data.bet;                             // visa insatsen
        showTable(data, true);                                 // rita korten
        showHands(data);                                       // visa summorna
        message.textContent = 'Du har en pågående hand. Vad gör du?';
        inHand = true;                                         // handen pågår
        canDouble = data.canDouble;
        canSplit = data.canSplit;
        updateButtons();                                       // slå på rätt knappar
    } catch (e) {
        // servern svarar inte: felet syns när man trycker på en knapp
    }
}

resume();                                                      // körs en gång när sidan öppnas
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
