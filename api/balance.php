<?php
/*
    Ger spelarens saldo som JSON: {"balance": 10000}
    Anropas av js/app.js på varje sida.
*/

require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $player = currentPlayer();
    echo json_encode(['balance' => (float) $player['balance']]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Databasfel.']);
}
