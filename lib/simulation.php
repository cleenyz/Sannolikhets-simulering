<?php
/*
|--------------------------------------------------------------------------
| lib/simulation.php
|--------------------------------------------------------------------------
|
| Teori + simulering för alla spel i LuckLab.
|
| Alla spel räknas som NETTORESULTAT PER 1 SATSAD CREDIT:
|
|     förlust = -1
|     vinst   = utbetalning - 1      (utbetalning 2x  ->  +1)
|
| Filen har två helt separata delar:
|
|   1. TEORI      räknar ut väntevärdet med sannolikhetslära (ingen slump)
|   2. SIMULERING spelar spelet n gånger med slumptal
|
| De måste vara separata. Om simuleringen byggde på samma siffror som
| teorin skulle vi jämföra något med sig självt och inget bevisas.
|
*/

const HOUSE_EDGE = 0.01;   // 1 % för Dice (Coinflip är ett rättvist spel, se nedan)


/* Roulette (europeisk, 37 fält: 0-36) */

const ROULETTE_RED = [1, 3, 5, 7, 9, 12, 14, 16, 18, 19, 21, 23, 25, 27, 30, 32, 34, 36];


/*
    Slots: tre likadana hjul.
    WEIGHTS = hur vanlig symbolen är på ett hjul (totalt 15 platser).
    PAYOUT  = total utbetalning per satsad credit vid tre lika.
    Ändra siffrorna och se hur väntevärdet ändras!
*/

const SLOT_WEIGHTS = ['Sjua' => 1, 'BAR' => 2, 'Klocka' => 3, 'Korsbar' => 4, 'Citron' => 5];
const SLOT_PAYOUT  = ['Sjua' => 500, 'BAR' => 100, 'Klocka' => 30, 'Korsbar' => 10, 'Citron' => 4];


/*
|--------------------------------------------------------------------------
| DEL 1: TEORI
|--------------------------------------------------------------------------
|
| Input: lista med [sannolikhet, nettoresultat].
|
| Väntevärde:   EV = Σ (sannolikhet × utfall)
| Varians:      Var = Σ (sannolikhet × utfall²) - EV²
|
*/

function theoryStats(array $outcomes): array
{
    $ev  = 0.0;
    $ex2 = 0.0;

    foreach ($outcomes as $o) {
        $ev  += $o[0] * $o[1];
        $ex2 += $o[0] * $o[1] * $o[1];
    }

    $variance = $ex2 - $ev * $ev;

    return [
        'ev' => $ev,
        'sd' => sqrt(max(0.0, $variance)),
    ];
}


/*
|--------------------------------------------------------------------------
| DEL 2: SIMULERING (Monte Carlo)
|--------------------------------------------------------------------------
|
| $play är en funktion som spelar EN omgång och returnerar nettoresultatet.
| Vi spelar n omgångar och sparar:
|
|   mean   medelresultat per omgång
|   sd     standardavvikelse (stickprov)
|   se     standardfel = sd / √n  (hur osäkert medelvärdet är)
|   series medelvärdet efter 10, ..., n spel (till konvergensgrafen)
|
*/

function simulate(callable $play, int $n, int $checkpoints = 60): array
{
    /* Kontrollpunkter jämnt fördelade på log-skala mellan 10 och n. */

    $marks = [];

    for ($k = 0; $k < $checkpoints; $k++) {
        $marks[] = (int) round(10 * pow($n / 10, $k / ($checkpoints - 1)));
    }

    $marks     = array_values(array_unique($marks));
    $markCount = count($marks);
    $next      = 0;

    $sum    = 0.0;
    $sumSq  = 0.0;
    $series = [];

    for ($i = 1; $i <= $n; $i++) {

        $x = $play();

        $sum   += $x;
        $sumSq += $x * $x;

        if ($next < $markCount && $i === $marks[$next]) {
            $series[] = [$i, $sum / $i];
            $next++;
        }
    }

    $mean     = $sum / $n;
    $variance = ($sumSq - $sum * $sum / $n) / ($n - 1);
    $sd       = sqrt(max(0.0, $variance));
    $se       = $sd / sqrt($n);

    return [
        'n'      => $n,
        'mean'   => $mean,
        'sd'     => $sd,
        'se'     => $se,
        'ci_low' => $mean - 1.96 * $se,
        'ci_high'=> $mean + 1.96 * $se,
        'series' => $series,
    ];
}


/*
|--------------------------------------------------------------------------
| SPEL: lista som visas i rullistan
|--------------------------------------------------------------------------
*/

function gamePresets(): array
{
    return [
        'coinflip'          => 'Coinflip (utbetalning 2x, rättvist spel)',
        'dice50'            => 'Dice, target 50',
        'dice10'            => 'Dice, target 10',
        'dice90'            => 'Dice, target 90',
        'roulette_red'      => 'Roulette: röd eller svart',
        'roulette_dozen'    => 'Roulette: dussin (1-12)',
        'roulette_straight' => 'Roulette: ett nummer',
        'slots'             => 'Slots (3 hjul)',
        'bj_basic'          => 'Blackjack: grundstrategi',
        'bj_dealer'         => 'Blackjack: spela som dealern',
        'bj_stand12'        => 'Blackjack: stanna på 12 eller mer',
        'bj_random'         => 'Blackjack: slumpa varje beslut',
    ];
}


/*
|--------------------------------------------------------------------------
| SPEL: teori (outcomes) + spelregler (play) för varje spel
|--------------------------------------------------------------------------
|
| 'outcomes' = teoretiska sannolikheter, uträknade för hand
| 'play'     = själva spelet med slumptal
|
| Blackjack har outcomes = null: det finns ingen enkel formel för
| väntevärdet, och det är precis därför man simulerar.
|
*/

function gameModel(string $key): array
{
    switch ($key) {

        /* ---------- COINFLIP ---------- */

        case 'coinflip':

            $win = 1.0;                           // utbetalning 2x: vinst +1, ingen husfördel (som i coinflip.php)

            return [
                'outcomes' => [[0.5, $win], [0.5, -1.0]],
                'play'     => function () use ($win) {
                    return mt_rand(0, 1) === 0 ? $win : -1.0;
                },
            ];


        /* ---------- DICE ---------- */

        case 'dice50':
        case 'dice10':
        case 'dice90':

            $target = (int) substr($key, 4);       // 50, 10 eller 90
            $p      = $target / 100;               // vinstchans
            $win    = (1 / $p) * (1 - HOUSE_EDGE) - 1;

            /*
                Slumptalet är ett heltal 0-9999 (10 000 lika sannolika utfall).
                Vinst om talet är mindre än target * 100.
                Target 50 -> 5000 av 10 000 utfall = exakt 50 %.
            */

            $limit = $target * 100;

            return [
                'outcomes' => [[$p, $win], [1 - $p, -1.0]],
                'play'     => function () use ($win, $limit) {
                    return mt_rand(0, 9999) < $limit ? $win : -1.0;
                },
            ];


        /* ---------- ROULETTE ---------- */

        case 'roulette_straight':

            return [
                'outcomes' => [[1 / 37, 35.0], [36 / 37, -1.0]],
                'play'     => function () {
                    return mt_rand(0, 36) === 17 ? 35.0 : -1.0;
                },
            ];

        case 'roulette_red':

            return [
                'outcomes' => [[18 / 37, 1.0], [19 / 37, -1.0]],
                'play'     => function () {
                    return in_array(mt_rand(0, 36), ROULETTE_RED, true) ? 1.0 : -1.0;
                },
            ];

        case 'roulette_dozen':

            return [
                'outcomes' => [[12 / 37, 2.0], [25 / 37, -1.0]],
                'play'     => function () {
                    $n = mt_rand(0, 36);
                    return ($n >= 1 && $n <= 12) ? 2.0 : -1.0;
                },
            ];


        /* ---------- SLOTS ---------- */

        case 'slots':

            /*
                Hjulen är oberoende, så sannolikheten för tre lika
                är (vikt / totalvikt) upphöjt till 3.
            */

            $total    = array_sum(SLOT_WEIGHTS);
            $outcomes = [];
            $pAnyWin  = 0.0;

            foreach (SLOT_WEIGHTS as $symbol => $weight) {

                $p = pow($weight / $total, 3);

                $outcomes[] = [$p, (float) (SLOT_PAYOUT[$symbol] - 1)];

                $pAnyWin += $p;
            }

            $outcomes[] = [1 - $pAnyWin, -1.0];

            return [
                'outcomes' => $outcomes,
                'play'     => function () {
                    return slotsSpin();
                },
            ];


        /* ---------- BLACKJACK ---------- */

        case 'bj_basic':
        case 'bj_dealer':
        case 'bj_stand12':
        case 'bj_random':

            $strategy = substr($key, 3);           // basic, dealer, stand12, random

            $deck  = range(0, 51);
            $stats = new stdClass();
            $stats->blackjacks = 0;

            return [
                'outcomes' => null,
                'stats'    => $stats,
                'play'     => function () use (&$deck, $strategy, $stats) {
                    return bjPlayHand($deck, $strategy, $stats);
                },
            ];
    }

    throw new InvalidArgumentException('Okänt spel: ' . $key);
}


/*
|--------------------------------------------------------------------------
| SLOTS: en snurr
|--------------------------------------------------------------------------
*/

function slotsSpin(): float
{
    $total = array_sum(SLOT_WEIGHTS);
    $reels = [];

    for ($i = 0; $i < 3; $i++) {

        $x = mt_rand(1, $total);

        foreach (SLOT_WEIGHTS as $symbol => $weight) {

            if ($x <= $weight) {
                $reels[$i] = $symbol;
                break;
            }

            $x -= $weight;
        }
    }

    if ($reels[0] === $reels[1] && $reels[1] === $reels[2]) {
        return (float) (SLOT_PAYOUT[$reels[0]] - 1);
    }

    return -1.0;
}


/*
|--------------------------------------------------------------------------
| BLACKJACK
|--------------------------------------------------------------------------
|
| Samma regler som i games/blackjack.php:
|   - ny blandad lek varje hand
|   - dealern drar till minst 17 (stannar på alla 17)
|   - blackjack betalar 3:2
|   - bara HIT och STAND (ingen dubbling eller split)
|
| Kort är heltal 0-51:  kort % 13  ->  0 = ess, 1-8 = 2-9, 9-12 = 10/J/Q/K
|
*/

function bjValue(int $card): int
{
    $rank = $card % 13;

    if ($rank === 0) {
        return 11;
    }

    if ($rank >= 9) {
        return 10;
    }

    return $rank + 1;
}


/*
    Returnerar [summa, mjuk?]
    Mjuk hand = ett ess räknas fortfarande som 11.
*/

function bjTotal(array $cards): array
{
    $total = 0;
    $aces  = 0;

    foreach ($cards as $card) {

        $v      = bjValue($card);
        $total += $v;

        if ($v === 11) {
            $aces++;
        }
    }

    while ($total > 21 && $aces > 0) {
        $total -= 10;
        $aces--;
    }

    return [$total, $aces > 0];
}


/*
    Drar nästa kort genom att byta in ett slumpat kort på plats $pos.
    Leken behöver aldrig återställas, den är alltid en giltig blandning.
*/

function bjDraw(array &$deck, int &$pos): int
{
    $j = mt_rand($pos, 51);

    $tmp        = $deck[$pos];
    $deck[$pos] = $deck[$j];
    $deck[$j]   = $tmp;

    return $deck[$pos++];
}


/*
    Strategier (bara hit/stand):

    dealer   drar tills 17, precis som dealern
    stand12  stannar på 12 eller mer ("ta aldrig risken att busta")
    random   slumpar varje beslut
    basic    grundstrategi: beslutet beror på dealerns uppvända kort
*/

function bjPlayerHits(string $strategy, int $total, bool $soft, int $dealerUp): bool
{
    switch ($strategy) {

        case 'dealer':
            return $total < 17;

        case 'stand12':
            return $total < 12;

        case 'random':
            return mt_rand(0, 1) === 1;

        case 'basic':

            if ($soft) {

                if ($total <= 17) {
                    return true;
                }

                if ($total === 18) {
                    return $dealerUp >= 9;          // dealern har 9, 10 eller ess
                }

                return false;
            }

            if ($total <= 11) {
                return true;
            }

            if ($total === 12) {
                return $dealerUp < 4 || $dealerUp > 6;
            }

            if ($total <= 16) {
                return $dealerUp > 6;
            }

            return false;
    }

    return false;
}


/*
    Spelar en hand. Returnerar nettoresultat: +1.5, +1, 0 eller -1.
*/

function bjPlayHand(array &$deck, string $strategy, stdClass $stats): float
{
    $pos = 0;

    /* Samma ordning som i blackjack.php: spelare, dealer, spelare, dealer */

    $player   = [bjDraw($deck, $pos)];
    $dealer   = [bjDraw($deck, $pos)];
    $player[] = bjDraw($deck, $pos);
    $dealer[] = bjDraw($deck, $pos);

    $playerBJ = bjTotal($player)[0] === 21;
    $dealerBJ = bjTotal($dealer)[0] === 21;

    if ($playerBJ) {
        $stats->blackjacks++;
    }

    if ($playerBJ || $dealerBJ) {

        if ($playerBJ && $dealerBJ) {
            return 0.0;
        }

        return $playerBJ ? 1.5 : -1.0;
    }

    $dealerUp = bjValue($dealer[0]);          // dealerns synliga kort

    while (true) {

        $t = bjTotal($player);

        if ($t[0] >= 21) {
            break;
        }

        if (!bjPlayerHits($strategy, $t[0], $t[1], $dealerUp)) {
            break;
        }

        $player[] = bjDraw($deck, $pos);
    }

    $playerTotal = bjTotal($player)[0];

    if ($playerTotal > 21) {
        return -1.0;
    }

    while (bjTotal($dealer)[0] < 17) {
        $dealer[] = bjDraw($deck, $pos);
    }

    $dealerTotal = bjTotal($dealer)[0];

    if ($dealerTotal > 21 || $playerTotal > $dealerTotal) {
        return 1.0;
    }

    if ($playerTotal === $dealerTotal) {
        return 0.0;
    }

    return -1.0;
}
