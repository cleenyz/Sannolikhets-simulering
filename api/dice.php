<?php
/*
    api/dice.php
    Servern slår tärningen, så spelaren kan inte påverka utfallet.

    Spelaren väljer en gräns (target) 2–98 och "under" eller "över":
        under 30  -> vinst om slaget är under 30,00  (chans 30 %)
        över  30  -> vinst om slaget är över  30,00  (chans 70 %)

    Multiplikator = 99 / chans   (99 i stället för 100 = 1 % husfördel)
    Samma regler som Dice i lib/simulation.php.

    Tar emot:  {"bet": 100, "target": 50, "mode": "under"}
    Svarar:    {"roll": 23.17, "win": true, "net": 98, "multiplier": 1.98, "balance": 10098}
*/

require __DIR__ . '/../includes/game.php';                 // fail(), readInput(), readBet(), saveRound()

$input  = readInput();                                     // det webbläsaren skickade
$bet    = readBet($input);                                 // insatsen, minst 1
$target = (int) ($input['target'] ?? 0);                   // gränsen, heltal
$mode   = $input['mode'] ?? '';                            // "under" eller "over"

if ($target < 2 || $target > 98) {                         // samma gränser som reglaget på sidan
    fail('Target måste vara mellan 2 och 98.');
}

if (!in_array($mode, ['under', 'over'], true)) {           // bara två giltiga val
    fail('Ogiltigt val.');
}

$chance     = $mode === 'under' ? $target : 100 - $target; // vinstchans i procent
$multiplier = 99 / $chance;                                // utbetalning per satsad credit

$roll = random_int(0, 9999);                               // 10 000 lika troliga utfall: 0,00–99,99

if ($mode === 'under') {
    $win = $roll < $target * 100;                          // t.ex. target 50: 0–4999 vinner = 5000 utfall = 50 %
} else {
    $win = $roll >= $target * 100;                         // t.ex. target 50: 5000–9999 vinner = 50 %
}

$net = $win ? $bet * ($multiplier - 1) : -$bet;            // vinst: utbetalning minus insatsen. Förlust: hela insatsen

$balance = saveRound('dice', $bet, $net, $mode . ' ' . $target . ': ' . ($roll / 100));  // spara och få nytt saldo

echo json_encode([                                         // svaret till webbläsaren
    'roll'       => $roll / 100,                           // 2317 -> 23.17
    'win'        => $win,
    'net'        => round($net, 2),
    'multiplier' => round($multiplier, 4),
    'balance'    => $balance,
]);
