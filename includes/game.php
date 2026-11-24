<?php
/*
    includes/game.php
    Gemensam kod för ALLA spel-API:er (api/coinflip.php, api/dice.php, ...).

    Ett spel-API gör bara tre saker:
        1. $input = readInput();  $bet = readBet($input);
        2. slumpa och räkna ut $net  (vinst = +, förlust = -)
        3. $balance = saveRound('spelnamn', $bet, $net, 'vad som hände');
*/

require_once __DIR__ . '/db.php';                          // ger oss db() och currentPlayer()

header('Content-Type: application/json; charset=utf-8');   // vi svarar alltid med JSON


/* Skickar ett felmeddelande till webbläsaren och stoppar programmet. */
function fail(string $message, int $code = 400): void
{
    http_response_code($code);                             // 400 = fel från spelaren, 500 = fel på servern
    echo json_encode(['error' => $message]);               // t.ex. {"error": "Ogiltig insats."}
    exit;                                                  // inget mer körs efter detta
}


/* Läser det webbläsaren skickade, t.ex. {"bet": 100, "choice": "heads"}. */
function readInput(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {           // spel får bara spelas med POST
        fail('Använd POST.');
    }

    $input = json_decode(file_get_contents('php://input'), true);   // texten -> PHP-array

    return is_array($input) ? $input : [];                 // trasig JSON blir en tom array
}


/* Plockar ut insatsen och kollar att den är ett heltal på minst 1. */
function readBet(array $input): int
{
    $bet = (int) ($input['bet'] ?? 0);                     // saknas den blir den 0

    if ($bet < 1) {
        fail('Ogiltig insats.');
    }

    return $bet;
}


/*
    Sparar en spelad omgång och returnerar spelarens nya saldo.
    $net = nettoresultat: +98 vid vinst, -100 vid förlust osv.
    Allt sker i en transaktion: antingen sparas ALLT eller INGENTING.
*/
function saveRound(string $game, int $bet, float $net, string $detail): float
{
    $pdo    = db();
    $player = currentPlayer();
    $net    = round($net, 2);                              // pengar med max 2 decimaler

    try {
        $pdo->beginTransaction();                          // starta "allt eller inget"

        // Ändra saldot, men BARA om spelaren har råd med insatsen.
        // Kollen ligger i samma fråga, så två snabba klick kan aldrig ge minussaldo.
        $st = $pdo->prepare('UPDATE players SET balance = balance + ? WHERE id = ? AND balance >= ?');
        $st->execute([$net, $player['id'], $bet]);

        if ($st->rowCount() === 0) {                       // 0 ändrade rader = saldot räckte inte
            $pdo->rollBack();                              // ångra allt
            fail('Du har inte tillräckligt med saldo.');
        }

        // Spara omgången (används till statistik och rapporten)
        $pdo->prepare('INSERT INTO rounds (player_id, game, bet, net, detail) VALUES (?, ?, ?, ?, ?)')
            ->execute([$player['id'], $game, $bet, $net, $detail]);

        // Hämta det nya saldot
        $st = $pdo->prepare('SELECT balance FROM players WHERE id = ?');
        $st->execute([$player['id']]);
        $balance = (float) $st->fetchColumn();

        $pdo->commit();                                    // spara allt på riktigt

        return $balance;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();                              // något gick fel: ångra allt
        }
        fail('Databasfel.', 500);                          // visa inte interna detaljer för spelaren
    }
}
