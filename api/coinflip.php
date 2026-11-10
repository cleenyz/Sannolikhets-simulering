<?php
/*
    Servern slumpar myntet, räknar ut vinst och uppdaterar saldot.
    Webbläsaren får bara resultatet, så spelaren kan inte påverka utfallet.
    Varje omgång sparas i databasen (tabellen rounds).

    Tar emot JSON:  {"bet": 100, "choice": "heads"}
    Svarar med:     {"result": "heads", "win": true, "net": 100, "balance": 10100}
*/

require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

function fail(string $message): void
{
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Använd POST.');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = [];
}

$bet    = (int) ($input['bet'] ?? 0);
$choice = $input['choice'] ?? '';

if (!in_array($choice, ['heads', 'tails'], true)) {
    fail('Ogiltigt val.');
}

if ($bet < 1) {
    fail('Ogiltig insats.');
}

try {

    $pdo    = db();
    $player = currentPlayer();

    if ($bet > (float) $player['balance']) {
        fail('Du har inte tillräckligt med saldo.');
    }

    /* Själva spelet: 50/50 */

    $result = random_int(0, 1) === 0 ? 'heads' : 'tails';
    $win    = $result === $choice;
    $net    = $win ? $bet : -$bet;       // nettoresultat: +insats eller -insats

    /* Spara saldo och omgång tillsammans (allt eller inget) */

    $pdo->beginTransaction();

    $pdo->prepare('UPDATE players SET balance = balance + ? WHERE id = ?')
        ->execute([$net, $player['id']]);

    $pdo->prepare('INSERT INTO rounds (player_id, game, bet, net, detail) VALUES (?, ?, ?, ?, ?)')
        ->execute([$player['id'], 'coinflip', $bet, $net, $result]);

    $st = $pdo->prepare('SELECT balance FROM players WHERE id = ?');
    $st->execute([$player['id']]);
    $balance = (float) $st->fetchColumn();

    $pdo->commit();

    echo json_encode([
        'result'  => $result,
        'win'     => $win,
        'net'     => $net,
        'balance' => $balance,
    ]);

} catch (Throwable $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['error' => 'Databasfel: ' . $e->getMessage()]);
}
