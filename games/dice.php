<?php
$base  = '../';                                            // sidan ligger i games/, så rotmappen är ../
$title = 'Dice';                                           // syns i webbläsarfliken
include __DIR__ . '/../includes/header.php';               // meny och saldo


/*
    Tabell till informationsdelen, uträknad med formler (ingen slump).
    p = vinstchans, m = 99 / chans = multiplikator.

    EV per satsad credit  = p · (m − 1) + (1 − p) · (−1)  = −0,01  (alltid −1 %)
    Standardavvikelse σ   = m · √(p · (1 − p))
    Spel tills husfördelen syns ≈ (1,96 · σ / 0,01)²
        = antal spel innan slumpen (±1,96 · σ/√n) blir mindre än husfördelen (1 %)
*/

$rows = [];                                                // en rad per vinstchans

foreach ([10, 25, 50, 75, 90] as $chance) {

    $p     = $chance / 100;                                // 50 -> 0,5
    $m     = 99 / $chance;                                 // multiplikator
    $ev    = $p * ($m - 1) + (1 - $p) * -1;                // väntevärde per satsad credit
    $sd    = $m * sqrt($p * (1 - $p));                     // standardavvikelse per satsad credit
    $games = pow(1.96 * $sd / 0.01, 2);                    // spel tills husfördelen syns

    $rows[] = [$chance, $m, $ev, $sd, $games];             // spara raden
}

function sv($x, $decimals)                                 // skriver tal på svenskt sätt: 1 234,56
{
    return number_format($x, $decimals, ',', ' ');
}
?>

<main>
  <div class="game-layout">

    <!-- ===== SPELET ===== -->
    <section class="game-box">
        <h1>Dice</h1>

        <!-- Senaste slaget, stort i mitten -->
        <div class="dice-result" id="result">–</div>

        <!-- Reglaget: grönt = vinst, rött = förlust. Markören visar senaste slaget -->
        <div class="dice-slider">
            <div class="dice-marker" id="marker" hidden></div>
            <input type="range" id="target" min="2" max="98" value="50">
            <div class="dice-scale"><span>0</span><span>25</span><span>50</span><span>75</span><span>100</span></div>
        </div>

        <!-- Tre rutor med siffrorna för valt target -->
        <div class="dice-boxes">
            <div><span>Multiplikator</span><strong id="multiplier">1,98×</strong></div>
            <div>
                <span id="mode-label">Slå under</span>
                <strong><button type="button" id="mode" class="dice-mode">50 ⇄</button></strong>
            </div>
            <div><span>Vinstchans</span><strong id="chance">50 %</strong></div>
        </div>

        <label for="bet">Insats</label>
        <div class="dice-bet">
            <input type="number" id="bet" min="1" value="100">
            <button type="button" id="half">½</button>
            <button type="button" id="double">2×</button>
        </div>
        <p class="info" id="payout">Vinst vid träff: +98</p>

        <button class="button" id="play">Slå tärningen</button>

        <p id="message" class="message">Välj target och insats.</p>
    </section>

    <!-- ===== LIVE STATS (byggs av js/stats.js) ===== -->
    <aside class="live-stats" id="live-stats"></aside>

  </div>


  <!-- ===== INFORMATION: kopplad till frågeställningen ===== -->
  <section class="dice-info">

    <h2>Så fungerar Dice</h2>

    <div class="dice-info-grid">

        <div class="sim-panel">
            <h3>Spelet</h3>
            <p>Servern slumpar ett tal mellan 0,00 och 99,99. Alla 10 000 tal är lika troliga.
               Du väljer en gräns och om talet ska hamna under eller över den.
               Dra reglaget åt ena hållet för större chans men mindre vinst, åt andra hållet för tvärtom.</p>
        </div>

        <div class="sim-panel">
            <h3>Chans och odds</h3>
            <p>Vinstchans <strong>p</strong> = antal vinnande tal / 10 000.
               Slå under 25 ger 2 500 vinnande tal, alltså p = 25 %.</p>
            <p>Multiplikator = 99 / chans. Ett rättvist spel hade haft 100 / chans.
               Skillnaden är casinots husfördel på 1 %.</p>
        </div>

        <div class="sim-panel">
            <h3>Väntevärde (EV)</h3>
            <p>EV = p · vinst + (1 − p) · förlust. Med insats 1 och chans 50 %:</p>
            <p class="dice-formula">0,5 · 0,98 + 0,5 · (−1) = −0,01</p>
            <p>Du förlorar i snitt 1 % av allt du satsar, oavsett vilket target du väljer.</p>
        </div>

        <div class="sim-panel">
            <h3>Varians</h3>
            <p>EV är alltid −1 %, men risken skiljer sig. Låg chans ger stora vinster sällan,
               alltså hög standardavvikelse (σ). Ju högre σ, desto fler spel krävs
               innan resultatet närmar sig EV. Det är lagen om stora tal.</p>
        </div>

    </div>


    <h3 class="dice-table-title">Teori för olika vinstchanser (per satsad credit)</h3>

    <table class="sim-table">
        <tr>
            <th>Vinstchans</th>
            <th class="num">Multiplikator</th>
            <th class="num">EV</th>
            <th class="num">Standardavvikelse σ</th>
            <th class="num">Spel tills husfördelen syns ≈</th>
        </tr>

        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r[0] ?> %</td>
                <td class="num"><?= sv($r[1], 4) ?>×</td>
                <td class="num"><?= sv($r[2], 4) ?></td>
                <td class="num"><?= sv($r[3], 3) ?></td>
                <td class="num"><?= sv($r[4], 0) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p class="info dice-note">
        Sista kolumnen: efter så många spel är slumpens svängningar (±1,96 · σ / √n) mindre än husfördelen.
        Innan dess kan man mycket väl ligga på plus. Det är därför casinon tjänar pengar i längden
        men enskilda spelare ibland vinner.
    </p>


    <!-- Din session jämfört med teorin. Fylls i av JavaScript nedan -->
    <div class="sim-panel dice-session">
        <h3>Din session: teori mot verklighet</h3>
        <table class="sim-table">
            <tr><td>Totalt satsat</td>                        <td class="num" id="s-wagered">0</td></tr>
            <tr><td>Förväntat resultat (EV = −1 % av satsat)</td><td class="num" id="s-expected">0</td></tr>
            <tr><td>Ditt faktiska resultat</td>               <td class="num" id="s-actual">0</td></tr>
        </table>
        <p class="info dice-note">
            Spela många gånger och se hur ditt resultat förhåller sig till förväntningen.
            Vill du se 100 000 spel på några sekunder? <a href="../simulation.php?game=dice50">Kör simuleringen</a>.
        </p>
    </div>

  </section>
</main>

<script src="../js/stats.js"></script>

<script>
// ---- Hämta elementen från sidan ----
const slider     = document.getElementById('target');     // reglaget
const modeButton = document.getElementById('mode');       // knappen som byter under/över
const betInput   = document.getElementById('bet');        // insatsfältet
const playButton = document.getElementById('play');       // "Slå tärningen"
const marker     = document.getElementById('marker');     // markören för senaste slaget
const result     = document.getElementById('result');     // stora siffran
const message    = document.getElementById('message');    // vinst/förlust-texten

const live = new LiveStats(document.getElementById('live-stats'), { key: 'dice' });  // live stats-panelen

let mode = 'under';                                        // börjar med "slå under"


// ---- Räkna om allt när target, läge eller insats ändras ----
function update() {
    const target = Number(slider.value);                  // t.ex. 50
    const chance = mode === 'under' ? target : 100 - target;   // vinstchans i procent
    const multiplier = 99 / chance;                       // samma formel som servern
    const bet = Number(betInput.value) || 0;              // tomt fält blir 0

    document.getElementById('multiplier').textContent = multiplier.toFixed(4).replace('.', ',') + '×';
    document.getElementById('chance').textContent = chance + ' %';
    document.getElementById('mode-label').textContent = mode === 'under' ? 'Slå under' : 'Slå över';
    modeButton.textContent = target + ' ⇄';
    document.getElementById('payout').textContent = 'Vinst vid träff: +' + fmt(bet * (multiplier - 1));

    // Färga reglaget: grön del = vinnande tal, röd del = förlorande tal
    const win = 'var(--win)', lose = 'var(--lose)';
    const left = mode === 'under' ? win : lose;           // färgen till vänster om gränsen
    const right = mode === 'under' ? lose : win;          // färgen till höger om gränsen
    slider.style.background = 'linear-gradient(to right, ' + left + ' ' + target + '%, ' + right + ' ' + target + '%)';
}


// ---- Visa sessionen: förväntat (teori) mot faktiskt resultat ----
function updateSession() {
    let wagered = 0, actual = 0;                          // summa satsat och summa resultat
    for (const b of live.bets) {                          // alla spel i live stats
        wagered += b.bet;
        actual += b.net;
    }
    const expected = -0.01 * wagered || 0;                // EV: −1 % av allt satsat (|| 0 så det inte står "−0")

    document.getElementById('s-wagered').textContent = fmt(wagered);
    document.getElementById('s-expected').textContent = fmt(expected);
    document.getElementById('s-actual').textContent = (actual > 0 ? '+' : '') + fmt(actual);
    document.getElementById('s-actual').className = 'num ' + (actual >= 0 ? 'ls-pos' : 'ls-neg');
}


// ---- Spela en omgång ----
async function play() {
    const bet = parseInt(betInput.value);                 // insatsen som heltal

    if (!bet || bet < 1) {                                // stoppa ogiltig insats direkt
        message.textContent = 'Ogiltig insats.';
        message.className = 'message lose';
        return;
    }

    playButton.disabled = true;                           // inga dubbelklick medan vi väntar

    try {
        const res = await fetch(BASE + 'api/dice.php', {  // fråga servern
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bet: bet, target: Number(slider.value), mode: mode })
        });
        const data = await res.json();                    // svaret som objekt

        if (!res.ok) {                                    // servern sa nej (t.ex. för lite saldo)
            message.textContent = data.error || 'Något gick fel.';
            message.className = 'message lose';
            return;
        }

        // Flytta markören till slaget och visa det stort
        marker.hidden = false;
        marker.style.left = data.roll + '%';              // 23.17 -> 23,17 % från vänster
        marker.textContent = data.roll.toFixed(2).replace('.', ',');
        marker.className = 'dice-marker ' + (data.win ? 'win' : 'lose');
        result.textContent = data.roll.toFixed(2).replace('.', ',');
        result.className = 'dice-result ' + (data.win ? 'win' : 'lose');

        message.textContent = data.win ? 'Vinst! +' + fmt(data.net) : 'Förlust. ' + fmt(data.net);
        message.className = 'message ' + (data.win ? 'win' : 'lose');

        showBalance(data.balance);                        // nya saldot i menyn
        live.add(bet, data.net);                          // lägg till i live stats
        updateSession();                                  // uppdatera teori mot verklighet

    } catch (e) {
        message.textContent = 'Kunde inte nå servern.';
        message.className = 'message lose';
    } finally {
        playButton.disabled = false;                      // knappen fungerar igen
    }
}


// ---- Koppla knappar och fält ----
slider.oninput = update;                                  // dra i reglaget
betInput.oninput = update;                                // skriv ny insats
modeButton.onclick = () => {                              // byt mellan under och över
    mode = mode === 'under' ? 'over' : 'under';
    update();
};
document.getElementById('half').onclick = () => {         // halvera insatsen (minst 1)
    betInput.value = Math.max(1, Math.floor(betInput.value / 2));
    update();
};
document.getElementById('double').onclick = () => {       // dubbla insatsen
    betInput.value = betInput.value * 2;
    update();
};
playButton.onclick = play;                                // spela
document.querySelector('.ls-reset').addEventListener('click', updateSession);  // "Återställ" i live stats

update();                                                 // rita allt en gång när sidan laddas
updateSession();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
