<?php
/*
    api/reset.php
    Sätter spelarens saldo tillbaka till 10 000 (knappen ↺ bredvid saldot).
    Spelade omgångar ligger kvar i databasen, de behövs till rapporten.

    Svarar:  {"balance": 10000}
*/

require __DIR__ . '/../includes/game.php';                 // fail(), readInput() m.m.

readInput();                                               // kollar bara att det är POST

$player = currentPlayer();                                 // vem är spelaren?

db()->prepare('UPDATE players SET balance = 10000 WHERE id = ?')
    ->execute([$player['id']]);                            // nollställ saldot

echo json_encode(['balance' => 10000]);                    // nya saldot till webbläsaren
