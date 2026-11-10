<?php
/*
|--------------------------------------------------------------------------
| includes/db.php
|--------------------------------------------------------------------------
|
| Databas med SQLite via PDO. Hela databasen är EN fil: data/lucklab.sqlite.
| Filen och tabellerna skapas automatiskt första gången sidan körs.
|
| Tabeller:
|   players   en rad per besökare (saldo)
|   rounds    varje spelad omgång (spel, insats, nettoresultat)
|   sim_runs  varje körning på simuleringssidan (för dokumentation)
|
| Använd:  $pdo = db();
|
*/

function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dir = __DIR__ . '/../data';

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
        // Apache/XAMPP: stäng av webbåtkomst till databasfilen
        file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }

    $pdo = new PDO('sqlite:' . $dir . '/lucklab.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $tables = [

        "CREATE TABLE IF NOT EXISTS players (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            token      TEXT NOT NULL UNIQUE,
            balance    REAL NOT NULL DEFAULT 10000,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS rounds (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            player_id  INTEGER NOT NULL REFERENCES players(id),
            game       TEXT NOT NULL,
            bet        REAL NOT NULL,
            net        REAL NOT NULL,
            detail     TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS sim_runs (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            game       TEXT NOT NULL,
            n          INTEGER NOT NULL,
            seed       INTEGER NOT NULL,
            mean       REAL NOT NULL,
            sd         REAL NOT NULL,
            theory_ev  REAL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    return $pdo;
}


/*
|--------------------------------------------------------------------------
| Spelaren för den här webbläsaren
|--------------------------------------------------------------------------
|
| Ingen inloggning. Webbläsaren får en slumpad nyckel i en cookie.
| Nyckeln pekar på en rad i players. Anropa bara från api/-filer
| (cookien måste skickas innan någon HTML skrivs ut).
|
*/

function currentPlayer(): array
{
    $pdo   = db();
    $token = $_COOKIE['lucklab_player'] ?? '';

    if (is_string($token) && preg_match('/^[a-f0-9]{32}$/', $token)) {

        $st = $pdo->prepare('SELECT * FROM players WHERE token = ?');
        $st->execute([$token]);
        $player = $st->fetch();

        if ($player) {
            return $player;
        }
    }

    $token = bin2hex(random_bytes(16));

    $pdo->prepare('INSERT INTO players (token) VALUES (?)')->execute([$token]);

    $id = (int) $pdo->lastInsertId();

    setcookie('lucklab_player', $token, time() + 60 * 60 * 24 * 365, '/');

    $st = $pdo->prepare('SELECT * FROM players WHERE id = ?');
    $st->execute([$id]);

    return $st->fetch();
}
