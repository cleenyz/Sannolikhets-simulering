<?php
$base = '../';
$title = 'Coinflip';
include __DIR__ . '/../includes/header.php';
?>

<main>
  <div class="game-layout">
    <section class="game-box">
        <h1>Coinflip</h1>
        <p class="info">Klave eller krona. Chans 1/2, vinst 2× insatsen.</p>

        <!-- MYNTET: heads.png på framsidan, tails.png på baksidan -->
        <div class="coin-area">
            <div class="coin" id="coin">
                <img class="side heads" src="../assets/img/heads.png" alt="Klave">
                <img class="side tails" src="../assets/img/tails.png" alt="Krona">
            </div>

            <!-- Valfri gif som visas medan myntet kastas (av som standard) -->
            <img class="flip-gif" id="flip-gif" src="../assets/img/coinflip.gif" alt="" hidden>
        </div>

        <p id="message" class="message">Välj insats och sida.</p>

        <label for="bet">Insats</label>
        <input type="number" id="bet" min="1" value="100">

        <div class="buttons">
            <button class="button" id="btn-heads">Klave</button>
            <button class="button" id="btn-tails">Krona</button>
        </div>

        <p class="stats" id="stats">Kast: 0 | Klave: 0 | Krona: 0</p>
    </section>

    <!-- Live stats (byggs av js/stats.js) -->
    <aside class="live-stats" id="live-stats"></aside>
  </div>
</main>

<script src="../js/stats.js"></script>

<script>
// ---- Inställningar ----
const USE_GIF = false;      // sätt till true om du lagt in assets/img/coinflip.gif
const FLIP_TIME = 1500;     // ms, ska vara samma som transition i style.css

// ---- Element ----
const coin = document.getElementById('coin');
const gif = document.getElementById('flip-gif');
const message = document.getElementById('message');
const stats = document.getElementById('stats');
const buttons = document.querySelectorAll('.buttons button');

const live = new LiveStats(document.getElementById('live-stats'), { key: 'coinflip' });

let rotation = 0;
let total = 0, heads = 0, tails = 0;

document.getElementById('btn-heads').onclick = () => flip('heads');
document.getElementById('btn-tails').onclick = () => flip('tails');

function showError(text) {
    message.textContent = text;
    message.className = 'message lose';
    buttons.forEach(b => b.disabled = false);
}

async function flip(choice) {
    const bet = parseInt(document.getElementById('bet').value);

    if (!bet || bet < 1) {
        showError('Ogiltig insats.');
        return;
    }

    buttons.forEach(b => b.disabled = true);
    message.textContent = 'Kastar...';
    message.className = 'message';

    // Servern slumpar myntet, räknar saldot och sparar omgången i databasen
    let data;
    try {
        const res = await fetch(BASE + 'api/coinflip.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bet: bet, choice: choice })
        });
        data = await res.json();

        if (!res.ok) {
            showError(data.error || 'Något gick fel.');
            return;
        }
    } catch (e) {
        showError('Kunde inte nå servern. Kör sajten med: php -S localhost:8000');
        return;
    }

    // Animation: snurra fyra varv och landa på rätt sida
    const target = data.result === 'heads' ? 0 : 180;
    rotation += 1440 + ((target - rotation % 360) + 360) % 360;
    coin.style.transform = 'rotateY(' + rotation + 'deg)';

    if (USE_GIF) { gif.hidden = false; coin.style.visibility = 'hidden'; }

    setTimeout(() => {
        if (USE_GIF) { gif.hidden = true; coin.style.visibility = 'visible'; }

        showBalance(data.balance);
        live.add(bet, data.net);

        total++;
        if (data.result === 'heads') heads++; else tails++;

        const pct = x => Math.round(100 * x / total);
        stats.textContent = 'Kast: ' + total +
            ' | Klave: ' + heads + ' (' + pct(heads) + ' %)' +
            ' | Krona: ' + tails + ' (' + pct(tails) + ' %)';

        message.textContent = data.win ? 'Vinst! +' + bet : 'Förlust. -' + bet;
        message.className = 'message ' + (data.win ? 'win' : 'lose');
        buttons.forEach(b => b.disabled = false);
    }, FLIP_TIME);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
