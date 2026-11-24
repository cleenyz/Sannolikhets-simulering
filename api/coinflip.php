<?php
/*
    api/coinflip.php
    Servern slumpar myntet, så spelaren kan inte påverka utfallet.

    Tar emot:  {"bet": 100, "choice": "heads"}
    Svarar:    {"result": "heads", "win": true, "net": 100, "balance": 10100}
*/

require __DIR__ . '/../includes/game.php';                 // fail(), readInput(), readBet(), saveRound()

$input  = readInput();                                     // det webbläsaren skickade
$bet    = readBet($input);                                 // insatsen, minst 1
$choice = $input['choice'] ?? '';                          // "heads" eller "tails"

if (!in_array($choice, ['heads', 'tails'], true)) {        // bara två giltiga val
    fail('Ogiltigt val.');
}

$result = random_int(0, 1) === 0 ? 'heads' : 'tails';      // 50/50, säker slump
$win    = $result === $choice;                             // gissade spelaren rätt?
$net    = $win ? $bet : -$bet;                             // utbetalning 2x: vinst +insats, förlust -insats

$balance = saveRound('coinflip', $bet, $net, $result);     // spara och få nytt saldo

echo json_encode([                                         // svaret till webbläsaren
    'result'  => $result,
    'win'     => $win,
    'net'     => $net,
    'balance' => $balance,
]);
