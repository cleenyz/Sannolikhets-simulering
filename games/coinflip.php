<?php
$base = '../';
$title = 'Coinflip';
include '../includes/header.php';
?>

<main>
    <section class="game-box">
        <h1>Coinflip</h1>
        <p class="info">Klave eller krona. Chans 1/2, vinst 2× insatsen.</p>

        <!-- MYNTET. Två sidor: heads och tails.
             Vill du ha egna bilder: se README i assets/img -->
        <div class="coin-area">
            <div class="coin" id="coin">
                <div class="side heads">K</div>
                <div class="side tails">Kr</div>
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
</main>

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

let rotation = 0;
let total = 0, heads = 0, tails = 0;

document.getElementById('btn-heads').onclick = () => flip('heads');
document.getElementById('btn-tails').onclick = () => flip('tails');

function flip(choice) {
    const bet = parseInt(document.getElementById('bet').value);

    if (!bet || bet < 1 || bet > getBalance()) {
        message.textContent = 'Ogiltig insats.';
        message.className = 'message lose';
        return;
    }

    // Slumpa: 50/50
    const result = Math.random() < 0.5 ? 'heads' : 'tails';

    // Animation: snurra fyra varv och landa på rätt sida
    const target = result === 'heads' ? 0 : 180;
    rotation += 1440 + ((target - rotation % 360) + 360) % 360;
    coin.style.transform = 'rotateY(' + rotation + 'deg)';

    if (USE_GIF) { gif.hidden = false; coin.style.visibility = 'hidden'; }

    buttons.forEach(b => b.disabled = true);
    message.textContent = 'Kastar...';
    message.className = 'message';

    setTimeout(() => {
        if (USE_GIF) { gif.hidden = true; coin.style.visibility = 'visible'; }

        const win = result === choice;
        setBalance(getBalance() + (win ? bet : -bet));

        total++;
        if (result === 'heads') heads++; else tails++;
        stats.textContent = 'Kast: ' + total + ' | Klave: ' + heads + ' | Krona: ' + tails;

        message.textContent = win ? 'Vinst! +' + bet : 'Förlust. -' + bet;
        message.className = 'message ' + (win ? 'win' : 'lose');
        buttons.forEach(b => b.disabled = false);
    }, FLIP_TIME);
}
</script>

<?php include '../includes/footer.php'; ?>
