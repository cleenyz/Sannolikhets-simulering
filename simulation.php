<?php

set_time_limit(60);

require __DIR__ . '/lib/simulation.php';


/*
|--------------------------------------------------------------------------
| INDATA (från formuläret, alltid kontrollerade)
|--------------------------------------------------------------------------
*/

$presets  = gamePresets();
$allowedN = [100, 1000, 10000, 100000, 1000000];

$key = (isset($_GET['game']) && is_string($_GET['game'])) ? $_GET['game'] : 'dice50';

if (!isset($presets[$key])) {
    $key = 'dice50';
}

$n = (int) ($_GET['n'] ?? 10000);

if (!in_array($n, $allowedN, true)) {
    $n = 10000;
}

$isBlackjack = strpos($key, 'bj_') === 0;

if ($isBlackjack && $n > 100000) {
    $n = 100000;                       // blackjack tar längre tid per spel
}

/*
    Seed = startvärde för slumptalen.
    Samma seed + samma inställningar = exakt samma resultat.
*/

$seedInput = $_GET['seed'] ?? '';

if (is_string($seedInput) && $seedInput !== '' && ctype_digit($seedInput)) {
    $seed = (int) $seedInput;
} else {
    $seed = random_int(1, 999999);
}


/*
|--------------------------------------------------------------------------
| KÖR SIMULERINGEN
|--------------------------------------------------------------------------
*/

mt_srand($seed);

$model  = gameModel($key);
$sim    = simulate($model['play'], $n);
$theory = $model['outcomes'] !== null ? theoryStats($model['outcomes']) : null;

/*
    Spara körningen i databasen (för dokumentationen i rapporten).
    Bara när formuläret skickats. Om databasen inte fungerar
    hoppar vi över det, simuleringen visas ändå.
*/

if (isset($_GET['game'])) {

    try {
        require_once __DIR__ . '/includes/db.php';

        db()->prepare('INSERT INTO sim_runs (game, n, seed, mean, sd, theory_ev) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$key, $n, $seed, $sim['mean'], $sim['sd'], $theory['ev'] ?? null]);

    } catch (Throwable $e) {
        // databasen är valfri här
    }
}


/*
|--------------------------------------------------------------------------
| DATA TILL GRAFEN
|--------------------------------------------------------------------------
*/

$chart = ['labels' => [], 'means' => []];

foreach ($sim['series'] as $point) {
    $chart['labels'][] = $point[0];
    $chart['means'][]  = round($point[1], 5);
}

if ($theory !== null) {

    $chart['ref']      = [];
    $chart['upper']    = [];
    $chart['lower']    = [];
    $chart['refLabel'] = 'Teoretiskt väntevärde';

    foreach ($chart['labels'] as $count) {

        $band = 1.96 * $theory['sd'] / sqrt($count);

        $chart['ref'][]   = round($theory['ev'], 5);
        $chart['upper'][] = round($theory['ev'] + $band, 5);
        $chart['lower'][] = round($theory['ev'] - $band, 5);
    }

} else {

    $chart['ref']      = array_fill(0, count($chart['labels']), 0);
    $chart['refLabel'] = 'Rättvist spel (väntevärde 0)';
}


/*
|--------------------------------------------------------------------------
| HJÄLPFUNKTIONER FÖR UTSKRIFT
|--------------------------------------------------------------------------
*/

function f4($x)
{
    return number_format($x + 0.0, 4, ',', ' ');
}

function pct($x)
{
    return number_format($x * 100 + 0.0, 2, ',', ' ') . ' %';
}

$theoryInCi = $theory !== null
    && $theory['ev'] >= $sim['ci_low']
    && $theory['ev'] <= $sim['ci_high'];

$newSeedUrl = '?game=' . urlencode($key) . '&n=' . $n;


/*
|--------------------------------------------------------------------------
| SIDA (samma header/footer som övriga sidor)
|--------------------------------------------------------------------------
*/

$base  = '';
$title = 'Simulering';
include __DIR__ . '/includes/header.php';

?>

<style>

    .sim-intro { color: var(--muted); max-width: 720px; margin-bottom: 24px; }

    .sim-form {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
        padding: 20px;
        margin-bottom: 24px;
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 12px;
    }

    .sim-form label { display: block; color: var(--muted); font-size: 14px; margin-bottom: 6px; }

    .sim-form select,
    .sim-form input {
        padding: 10px;
        font: 15px Georgia, serif;
        background: var(--bg);
        color: var(--text);
        border: 1px solid var(--line);
        border-radius: 6px;
    }

    .sim-form .again { color: var(--muted); font-size: 14px; padding-bottom: 12px; }
    .sim-form .again:hover { color: var(--text); }

    .sim-grid {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .sim-panel {
        padding: 20px;
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 12px;
    }

    .sim-panel h3 { font-size: 20px; margin-bottom: 12px; }

    .sim-table { width: 100%; border-collapse: collapse; font-size: 15px; }

    .sim-table th,
    .sim-table td {
        text-align: left;
        padding: 10px 6px;
        border-bottom: 1px solid var(--line);
    }

    .sim-table th { color: var(--muted); font-weight: normal; }
    .sim-table td.num { text-align: right; font-variant-numeric: tabular-nums; }

    .sim-chart { position: relative; height: 380px; }

    .ok   { color: var(--win); }
    .miss { color: var(--lose); }

    .sim-note { color: var(--muted); font-size: 14px; margin-top: 12px; }

    .sim-explain { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .sim-explain p { color: var(--muted); font-size: 14px; }

    @media (max-width: 900px) {
        .sim-grid,
        .sim-explain { grid-template-columns: 1fr; }
    }

</style>


<main>

    <h2>Stämmer teorin med verkligheten?</h2>

    <p class="sim-intro">
        Teorin räknar ut väntevärdet med formler. Datorn spelar sedan spelet många
        gånger med slumptal. Jämför de två.
    </p>


    <!-- FORMULÄR -->

    <form method="GET" class="sim-form">

        <div>
            <label for="game">Spel</label>

            <select name="game" id="game">

                <?php foreach ($presets as $k => $label): ?>

                    <option value="<?= htmlspecialchars($k) ?>" <?= $k === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div>
            <label for="n">Antal spel (n)</label>

            <select name="n" id="n">

                <?php foreach ($allowedN as $option): ?>

                    <option value="<?= $option ?>" <?= $option === $n ? 'selected' : '' ?>>
                        <?= number_format($option, 0, ',', ' ') ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div>
            <label for="seed">Seed</label>

            <input type="text" name="seed" id="seed" value="<?= $seed ?>" inputmode="numeric" pattern="[0-9]*">
        </div>

        <button type="submit" class="button">Kör simulering</button>

        <a href="<?= htmlspecialchars($newSeedUrl) ?>" class="again">Ny slumpning</a>

    </form>


    <!-- RESULTAT -->

    <div class="sim-grid">


        <div class="sim-panel">

            <h3>Resultat</h3>

            <table class="sim-table">

                <tr>
                    <th>Per satsad credit</th>
                    <th class="num">Teori</th>
                    <th class="num">Simulering</th>
                </tr>

                <tr>
                    <td>Väntevärde (EV)</td>
                    <td class="num"><?= $theory ? f4($theory['ev']) : '–' ?></td>
                    <td class="num"><?= f4($sim['mean']) ?></td>
                </tr>

                <tr>
                    <td>Husfördel</td>
                    <td class="num"><?= $theory ? pct(0 - $theory['ev']) : '–' ?></td>
                    <td class="num"><?= pct(0 - $sim['mean']) ?></td>
                </tr>

                <tr>
                    <td>Standardavvikelse per spel</td>
                    <td class="num"><?= $theory ? f4($theory['sd']) : '–' ?></td>
                    <td class="num"><?= f4($sim['sd']) ?></td>
                </tr>

                <tr>
                    <td>Standardfel (σ / √n)</td>
                    <td class="num"><?= $theory ? f4($theory['sd'] / sqrt($n)) : '–' ?></td>
                    <td class="num"><?= f4($sim['se']) ?></td>
                </tr>

                <?php if ($theory): ?>

                    <tr>
                        <td>Skillnad |sim − teori|</td>
                        <td class="num" colspan="2"><?= f4(abs($sim['mean'] - $theory['ev'])) ?></td>
                    </tr>

                <?php endif; ?>

                <tr>
                    <td>95 %-intervall för simulerat EV</td>
                    <td class="num" colspan="2">[<?= f4($sim['ci_low']) ?> ; <?= f4($sim['ci_high']) ?>]</td>
                </tr>

                <?php if ($theory): ?>

                    <tr>
                        <td>Ligger teorin i intervallet?</td>
                        <td class="num <?= $theoryInCi ? 'ok' : 'miss' ?>" colspan="2">
                            <?= $theoryInCi ? 'Ja' : 'Nej' ?>
                        </td>
                    </tr>

                <?php endif; ?>

                <?php if (isset($model['stats'])): ?>

                    <tr>
                        <td>Andel blackjack (kontroll)</td>
                        <td class="num"><?= pct(128 / 2652) ?></td>
                        <td class="num"><?= pct($model['stats']->blackjacks / $n) ?></td>
                    </tr>

                <?php endif; ?>

            </table>

            <?php if ($theory): ?>

                <p class="sim-note">
                    Ett 95 %-intervall missar det sanna värdet i ungefär 1 av 20 körningar.
                    Ett "Nej" ibland är alltså helt normalt.
                </p>

            <?php else: ?>

                <p class="sim-note">
                    Blackjack har ingen enkel formel för väntevärdet. Därför är simulering
                    metoden. Kontrollraden jämför den del vi kan räkna ut exakt: chansen att
                    få blackjack i första given är 2 · (4/52) · (16/51) ≈ 4,83 %.
                </p>

            <?php endif; ?>

        </div>


        <div class="sim-panel">

            <h3>Medelresultat efter n spel</h3>

            <div class="sim-chart">
                <canvas id="chart"></canvas>
            </div>

            <?php if ($theory): ?>

                <p class="sim-note">
                    Tratten visar vad teorin förutsäger: i ungefär 95 % av fallen ska
                    medelvärdet ligga inom EV ± 1,96 · σ/√n. Tratten smalnar av när n växer,
                    och det är lagen om stora tal.
                </p>

            <?php endif; ?>

        </div>


    </div>


    <!-- FÖRKLARING -->

    <div class="sim-explain">

        <div class="sim-panel">
            <h3>Väntevärde</h3>
            <p>
                EV = Σ (sannolikhet × utfall). Genomsnittet per spel om man spelar
                oändligt många gånger. Negativt EV är husfördelen.
            </p>
        </div>

        <div class="sim-panel">
            <h3>Standardavvikelse</h3>
            <p>
                Visar hur mycket enskilda spel varierar. Roulette på ett nummer har samma
                EV som röd/svart men mycket större variation.
            </p>
        </div>

        <div class="sim-panel">
            <h3>Seed</h3>
            <p>
                Datorns slumptal räknas fram från ett startvärde. Med samma seed får ni
                exakt samma resultat igen, så en körning kan redovisas och kontrolleras.
            </p>
        </div>

    </div>

</main>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>

<script>

    const data = <?= json_encode($chart) ?>;

    const datasets = [];

    if (data.upper) {

        datasets.push({
            label: 'Teori: EV + 1,96 · σ/√n',
            data: data.upper,
            borderColor: '#2f6b66',
            borderDash: [4, 4],
            borderWidth: 1,
            pointRadius: 0
        });

        datasets.push({
            label: 'Teori: EV − 1,96 · σ/√n',
            data: data.lower,
            borderColor: '#2f6b66',
            borderDash: [4, 4],
            borderWidth: 1,
            pointRadius: 0,
            fill: '-1',
            backgroundColor: 'rgba(217, 169, 79, 0.08)'
        });
    }

    datasets.push({
        label: data.refLabel,
        data: data.ref,
        borderColor: '#6fd39b',
        borderDash: [8, 4],
        borderWidth: 2,
        pointRadius: 0
    });

    datasets.push({
        label: 'Simulerat medelvärde',
        data: data.means,
        borderColor: '#d9a94f',
        borderWidth: 2,
        pointRadius: 0,
        tension: 0.15
    });

    Chart.defaults.color = '#9fbab2';

    new Chart(document.getElementById('chart'), {
        type: 'line',
        data: { labels: data.labels, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: {
                x: {
                    title: { display: true, text: 'Antal spel (n), log-skala' },
                    ticks: { maxTicksLimit: 8 },
                    grid: { color: '#1f4d4a' }
                },
                y: {
                    title: { display: true, text: 'Medelresultat per satsad credit' },
                    grid: { color: '#1f4d4a' }
                }
            },
            plugins: {
                legend: { labels: { boxWidth: 14 } }
            }
        }
    });

</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
