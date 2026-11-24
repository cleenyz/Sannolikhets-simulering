<?php
/*
    api/blackjack.php
    Hela blackjack-spelet körs på servern. Leken och dealerns dolda kort
    finns bara här, så spelaren kan inte fuska genom att titta i webbläsaren.

    Samma regler som simuleringen (lib/simulation.php):
        - ny blandad lek (52 kort) varje hand
        - dealern drar till 17 och stannar på alla 17
        - blackjack betalar 3:2, lika = insatsen tillbaka
        - DOUBLE: på två kort (även efter split), dubbel insats, exakt ett kort till
        - SPLIT: två kort med samma värde, bara en gång. Delade ess får ett kort var.
        - 21 efter split räknas inte som blackjack (betalar 1:1)

    Tar emot:  {"action": "deal", "bet": 100}    ny hand
               {"action": "hit"}                  ett kort till
               {"action": "stand"}                stanna
               {"action": "double"}               dubbla
               {"action": "split"}                dela paret i två händer
               {"action": "state"}                pågående hand (om sidan laddas om)

    Svarar:    {"bet": 100, "hands": [{"cards": [12, 40], "bet": 100, "total": 20}],
                "active": 0, "dealer": [7], "dealerTotal": 8,
                "canDouble": true, "canSplit": false, "done": false}
               När handen är slut: "done": true, varje hand får "result" och "net",
               plus "net" (totalt) och "balance".

    Kort är heltal 0-51:  kort % 13 = valör (0 = ess),  kort / 13 = färg.
*/

require __DIR__ . '/../includes/game.php';                 // fail(), readInput(), readBet(), saveRound()
require __DIR__ . '/../lib/simulation.php';                // bjTotal(), bjValue(): SAMMA räkning som simuleringen

session_start();                                           // spelet sparas i sessionen mellan klicken


/* Drar ett slumpat kort ur leken och tar bort det, så det inte kan komma igen. */
function drawCard(array &$deck): int                       // & = vi ändrar själva leken, inte en kopia
{
    $i    = random_int(0, count($deck) - 1);               // slumpa en plats i leken (säker slump)
    $card = $deck[$i];                                     // kortet på den platsen
    array_splice($deck, $i, 1);                            // ta bort kortet ur leken
    return $card;                                          // ge tillbaka kortet
}


/* Skapar en spelarhand. */
function newHand(array $cards, int $bet): array
{
    return ['cards' => $cards, 'bet' => $bet, 'done' => false];   // done = spelaren är klar med handen
}


/* Summan av alla insatser just nu (kan ha ökat av double/split). */
function totalBet(array $game): int
{
    $sum = 0;                                              // börja på noll
    foreach ($game['hands'] as $h) {                       // för varje hand ...
        $sum += $h['bet'];                                 // ... lägg till dess insats
    }
    return $sum;
}


/* Får spelaren dubbla? Bara på de två första korten i handen. */
function canDouble(array $game): bool
{
    $h = $game['hands'][$game['active']];                  // handen som spelas nu
    return count($h['cards']) === 2 && !$h['done'];        // två kort och inte klar
}


/* Får spelaren splitta? Bara en gång, och bara två kort med samma värde. */
function canSplit(array $game): bool
{
    $cards = $game['hands'][0]['cards'];                   // första handens kort
    return count($game['hands']) === 1                     // inte redan splittat
        && count($cards) === 2                             // exakt två kort
        && bjValue($cards[0]) === bjValue($cards[1]);      // samma värde (t.ex. K och Q = 10 och 10)
}


/* Kollar att spelaren har råd att lägga $extra till på bordet. */
function checkMoney(array $game, int $extra): void
{
    if ((float) currentPlayer()['balance'] < totalBet($game) + $extra) {   // saldot räcker inte
        fail('Du har inte tillräckligt med saldo för det.');
    }
}


/* Det webbläsaren får se. Under spelet döljs dealerns andra kort. */
function view(array $game, bool $done): array
{
    $dealer = $done ? $game['dealer'] : [$game['dealer'][0]];   // dolt kort skickas inte alls
    $hands  = [];                                          // händerna i en enklare form

    foreach ($game['hands'] as $h) {
        $hands[] = [
            'cards'  => $h['cards'],                       // korten
            'bet'    => $h['bet'],                         // insatsen på just den handen
            'total'  => bjTotal($h['cards'])[0],           // summan ([0] = summan, [1] = mjuk?)
            'result' => $h['result'] ?? null,              // bara satt när spelet är slut
            'net'    => $h['net'] ?? null,                 // vinst/förlust för handen
        ];
    }

    return [
        'bet'         => $game['bet'],                     // grundinsatsen
        'hands'       => $hands,                           // en eller två händer
        'active'      => $game['active'],                  // vilken hand som spelas nu
        'dealer'      => $dealer,                          // dealerns kort som syns
        'dealerTotal' => bjTotal($dealer)[0],              // summan av dealerns synliga kort
        'canDouble'   => !$done && canDouble($game),       // ska Double-knappen vara på?
        'canSplit'    => !$done && canSplit($game),        // ska Split-knappen vara på?
        'done'        => $done,                            // är spelet slut?
    ];
}


/* Spelet är slut: spara i databasen och svara webbläsaren. */
function endGame(array $game): void
{
    unset($_SESSION['bj']);                                // spelet är slut, ta bort det ur sessionen

    $net   = 0;                                            // totalt resultat
    $parts = [];                                           // text till databasen, t.ex. "win 20"
    foreach ($game['hands'] as $h) {
        $net    += $h['net'];                              // summera alla händer
        $parts[] = $h['result'] . ' ' . bjTotal($h['cards'])[0];
    }
    $detail = implode(', ', $parts) . ' mot ' . bjTotal($game['dealer'])[0];   // t.ex. "win 20, bust 24 mot 18"

    $balance = saveRound('blackjack', totalBet($game), $net, $detail);   // insats = allt som låg på bordet

    $out            = view($game, true);                   // alla kort syns nu
    $out['net']     = $net;                                // totalt +/-
    $out['balance'] = $balance;                            // nytt saldo

    echo json_encode($out);                                // skicka svaret
    exit;                                                  // inget mer körs
}


/* Alla händer är klara: dealern spelar och varje hand jämförs med dealern. */
function finish(array $game): void
{
    $allBust = true;                                       // är alla spelarens händer tjocka?
    foreach ($game['hands'] as $h) {
        if (bjTotal($h['cards'])[0] <= 21) {
            $allBust = false;                              // minst en hand lever
        }
    }

    if (!$allBust) {                                       // annars behöver dealern inte dra
        while (bjTotal($game['dealer'])[0] < 17) {         // under 17: dealern MÅSTE dra
            $game['dealer'][] = drawCard($game['deck']);
        }
    }

    $d = bjTotal($game['dealer'])[0];                      // dealerns summa

    foreach ($game['hands'] as $i => $h) {                 // $i = handens nummer
        $p   = bjTotal($h['cards'])[0];                    // handens summa
        $bet = $h['bet'];                                  // handens insats (dubbel om man dubblat)

        if ($p > 21) {
            $result = 'bust';  $net = -$bet;               // tjock: förlorar alltid
        } elseif ($d > 21 || $p > $d) {
            $result = 'win';   $net = $bet;                // dealern tjock eller handen högre
        } elseif ($p === $d) {
            $result = 'push';  $net = 0;                   // lika
        } else {
            $result = 'lose';  $net = -$bet;               // dealern högre
        }

        $game['hands'][$i]['result'] = $result;            // spara resultatet på handen
        $game['hands'][$i]['net']    = $net;
    }

    endGame($game);
}


/* Går vidare till nästa hand som inte är klar. Finns ingen: spelet avgörs. */
function nextHand(array $game): void
{
    foreach ($game['hands'] as $i => $h) {
        if (bjTotal($h['cards'])[0] >= 21) {               // 21 eller tjock: inget mer att göra
            $game['hands'][$i]['done'] = true;
        }

        if (!$game['hands'][$i]['done']) {                 // första handen som inte är klar
            $game['active'] = $i;                          // den spelas nu
            $_SESSION['bj'] = $game;                       // spara till nästa klick
            echo json_encode(view($game, false));          // visa läget
            exit;
        }
    }

    finish($game);                                         // alla händer klara
}


$input  = readInput();                                     // det webbläsaren skickade
$action = $input['action'] ?? '';                          // deal, hit, stand, double, split eller state
$game   = $_SESSION['bj'] ?? null;                         // pågående spel, eller null om inget finns


/* ---------- STATE: sidan laddades om ---------- */

if ($action === 'state') {
    echo json_encode($game ? view($game, false) : ['active' => false]);   // spelet, eller "inget spel"
    exit;
}


/* ---------- DEAL: ny hand ---------- */

if ($action === 'deal') {

    if ($game) {                                           // man får inte "fly" från en dålig hand
        fail('Spela klart handen först.');
    }

    $bet = readBet($input);                                // heltal, minst 1

    if ((float) currentPlayer()['balance'] < $bet) {       // har spelaren råd?
        fail('Du har inte tillräckligt med saldo.');
    }

    $game = [
        'bet'    => $bet,                                  // grundinsatsen
        'deck'   => range(0, 51),                          // ny full lek: kort 0-51
        'dealer' => [],                                    // dealerns kort
        'hands'  => [newHand([], $bet)],                   // spelaren börjar med en tom hand
        'active' => 0,                                     // hand nummer 0 spelas
    ];

    $game['hands'][0]['cards'][] = drawCard($game['deck']);   // given i rätt ordning:
    $game['dealer'][]            = drawCard($game['deck']);   // spelare, dealer,
    $game['hands'][0]['cards'][] = drawCard($game['deck']);   // spelare,
    $game['dealer'][]            = drawCard($game['deck']);   // dealer (dolt kort)

    $playerBJ = bjTotal($game['hands'][0]['cards'])[0] === 21;   // 21 på två kort = blackjack
    $dealerBJ = bjTotal($game['dealer'])[0] === 21;              // samma för dealern

    if ($playerBJ || $dealerBJ) {                          // blackjack avgör direkt
        if ($playerBJ && $dealerBJ) {
            $result = 'push';      $net = 0;               // båda: lika
        } elseif ($playerBJ) {
            $result = 'blackjack'; $net = 1.5 * $bet;      // 3:2 -> vinst 1,5 × insatsen
        } else {
            $result = 'lose';      $net = -$bet;           // dealern har blackjack
        }
        $game['hands'][0]['result'] = $result;
        $game['hands'][0]['net']    = $net;
        endGame($game);
    }

    nextHand($game);                                       // spara och visa korten
}


/* ---------- Resten kräver ett pågående spel ---------- */

if (!$game) {
    fail('Ingen pågående hand. Tryck på Spela.');
}

$i = $game['active'];                                      // handen som spelas nu

if ($action === 'hit') {
    $game['hands'][$i]['cards'][] = drawCard($game['deck']);   // ett kort till
    nextHand($game);                                       // nextHand ser om handen blev tjock eller 21
}

if ($action === 'stand') {
    $game['hands'][$i]['done'] = true;                     // handen är klar
    nextHand($game);
}

if ($action === 'double') {
    if (!canDouble($game)) {
        fail('Du kan bara dubbla på två kort.');
    }
    checkMoney($game, $game['hands'][$i]['bet']);          // samma insats en gång till

    $game['hands'][$i]['bet'] *= 2;                        // dubbel insats
    $game['hands'][$i]['cards'][] = drawCard($game['deck']);   // exakt ett kort till
    $game['hands'][$i]['done'] = true;                     // sedan är handen klar
    nextHand($game);
}

if ($action === 'split') {
    if (!canSplit($game)) {
        fail('Du kan bara splitta två kort med samma värde, en gång.');
    }
    checkMoney($game, $game['bet']);                       // den nya handen kostar en insats

    $cards = $game['hands'][0]['cards'];                   // paret
    $aces  = bjValue($cards[0]) === 11;                    // ess-par?

    $game['hands'] = [                                     // två nya händer, ett kort var + ett nytt
        newHand([$cards[0], drawCard($game['deck'])], $game['bet']),
        newHand([$cards[1], drawCard($game['deck'])], $game['bet']),
    ];

    if ($aces) {                                           // delade ess: bara ett kort var
        $game['hands'][0]['done'] = true;
        $game['hands'][1]['done'] = true;
    }

    nextHand($game);
}

fail('Okänd handling.');                                   // action var något annat än ovan
