<?php

session_start();

/*
|--------------------------------------------------------------------------
| SALDO
|--------------------------------------------------------------------------
*/

/*if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 10000;
}
*/
$_SESSION['balance'] = 10000;
$balance = $_SESSION['balance'];


/*
|--------------------------------------------------------------------------
| BLACKJACK-SESSION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['blackjack'])) {
    $_SESSION['blackjack'] = [
        'deck' => [],
        'player' => [],
        'dealer' => [],
        'bet' => 0,
        'active' => false,
        'message' => '',
        'result' => ''
    ];
}

$game = &$_SESSION['blackjack'];


/*
|--------------------------------------------------------------------------
| KORTLEK
|--------------------------------------------------------------------------
*/

function createDeck()
{
    $suits = ['♠', '♥', '♦', '♣'];

    $values = [
        'A',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '10',
        'J',
        'Q',
        'K'
    ];

    $deck = [];

    foreach ($suits as $suit) {

        foreach ($values as $value) {

            $deck[] = [
                'value' => $value,
                'suit' => $suit
            ];
        }
    }

    shuffle($deck);

    return $deck;
}


/*
|--------------------------------------------------------------------------
| KORTETS VÄRDE
|--------------------------------------------------------------------------
*/

function cardValue($card)
{
    if ($card['value'] === 'A') {
        return 11;
    }

    if (
        $card['value'] === 'K' ||
        $card['value'] === 'Q' ||
        $card['value'] === 'J'
    ) {
        return 10;
    }

    return intval($card['value']);
}


/*
|--------------------------------------------------------------------------
| RÄKNA HAND
|--------------------------------------------------------------------------
*/

function calculateHand($hand)
{
    $total = 0;
    $aces = 0;

    foreach ($hand as $card) {

        $total += cardValue($card);

        if ($card['value'] === 'A') {
            $aces++;
        }
    }

    /*
        Ett ess räknas som 1 istället för 11
        om handen annars skulle bli över 21.
    */

    while ($total > 21 && $aces > 0) {

        $total -= 10;

        $aces--;
    }

    return $total;
}


/*
|--------------------------------------------------------------------------
| BLACKJACK?
|--------------------------------------------------------------------------
*/

function isBlackjack($hand)
{
    return count($hand) === 2 && calculateHand($hand) === 21;
}


/*
|--------------------------------------------------------------------------
| NYTT SPEL
|--------------------------------------------------------------------------
*/

function startGame(&$game, $bet)
{
    $game['deck'] = createDeck();

    $game['player'] = [];
    $game['dealer'] = [];

    $game['bet'] = $bet;

    $game['active'] = true;

    $game['message'] = '';
    $game['result'] = '';

    /*
        Player får två kort
        Dealer får två kort
    */

    $game['player'][] = array_pop($game['deck']);
    $game['dealer'][] = array_pop($game['deck']);

    $game['player'][] = array_pop($game['deck']);
    $game['dealer'][] = array_pop($game['deck']);
}


/*
|--------------------------------------------------------------------------
| AVSLUTA SPEL
|--------------------------------------------------------------------------
*/

function finishGame(&$game, &$balance)
{
    $playerTotal = calculateHand($game['player']);
    $dealerTotal = calculateHand($game['dealer']);

    /*
        Dealer drar tills minst 17
    */

    while ($dealerTotal < 17) {

        $game['dealer'][] = array_pop($game['deck']);

        $dealerTotal = calculateHand($game['dealer']);
    }


    /*
        BLACKJACK
    */

    if (isBlackjack($game['player']) && !isBlackjack($game['dealer'])) {

        /*
            Blackjack betalar 3:2

            100 credits -> 150 credits i nettovinst
        */

        $profit = $game['bet'] * 1.5;

        $balance += $profit;

        $game['message'] = 'BLACKJACK!';
        $game['result'] = 'win';
    }


    /*
        DEALER BLACKJACK
    */

    elseif (!isBlackjack($game['player']) && isBlackjack($game['dealer'])) {

        $game['message'] = 'Dealer fick Blackjack.';
        $game['result'] = 'lose';

    }


    /*
        PUSH
    */

    elseif ($playerTotal === $dealerTotal) {

        /*
            Insatsen returneras.
        */

        $balance += $game['bet'];

        $game['message'] = 'PUSH - lika resultat.';
        $game['result'] = 'push';
    }


    /*
        SPELAREN VINNER
    */

    elseif ($dealerTotal > 21 || $playerTotal > $dealerTotal) {

        /*
            1:1-vinst
        */

        $balance += $game['bet'] * 2;

        $game['message'] = 'DU VANN!';
        $game['result'] = 'win';
    }


    /*
        DEALER VINNER
    */

    else {

        $game['message'] = 'DEALER VANN.';
        $game['result'] = 'lose';
    }


    $game['active'] = false;

    $_SESSION['balance'] = $balance;
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | NYTT SPEL
    |--------------------------------------------------------------------------
    */

    if ($action === 'new_game') {

        $bet = floatval($_POST['bet'] ?? 0);


        if ($bet <= 0) {

            $game['message'] = 'Ange en giltig insats.';
            $game['result'] = 'error';

        }

        elseif ($bet > $balance) {

            /*
                Vi markerar att popup ska visas.
            */

            $game['message'] = 'Du har inte tillräckligt med pengar.';
            $game['result'] = 'money_error';

        }

        else {

            /*
                Dra insatsen från saldot.
            */

            $balance -= $bet;

            $_SESSION['balance'] = $balance;

            startGame($game, $bet);


            /*
                Direkt Blackjack
            */

            if (isBlackjack($game['player'])) {

                finishGame($game, $balance);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | HIT
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'hit' && $game['active']) {

        $game['player'][] = array_pop($game['deck']);

        $playerTotal = calculateHand($game['player']);


        /*
            Bust
        */

        if ($playerTotal > 21) {

            $game['message'] = 'DU BUSTADE!';
            $game['result'] = 'lose';

            $game['active'] = false;
        }


        /*
            21
        */

        elseif ($playerTotal === 21) {

            finishGame($game, $balance);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STAND
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'stand' && $game['active']) {

        finishGame($game, $balance);
    }


    $_SESSION['balance'] = $balance;
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$playerTotal = calculateHand($game['player']);
$dealerTotal = calculateHand($game['dealer']);

?>

<!DOCTYPE html>

<html lang="sv">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Blackjack | Probability Lab</title>

    <link
        rel="stylesheet"
        href="../css/blackjack.css"
    >

</head>


<body>


<div class="page">


    <!-- =========================================================
         TOPBAR
    ========================================================== -->

    <header class="topbar">

        <a
            href="../main.php"
            class="logo"
        >
            PROBABILITY LAB
        </a>


        <div class="balance">

            <span>Saldo</span>

            <strong>
                <?php
                echo number_format(
                    $balance,
                    2,
                    ',',
                    ' '
                );
                ?>
            </strong>

        </div>

    </header>



    <main class="container">


        <!-- =====================================================
             HEADER
        ====================================================== -->

        <section class="page-header">

            <span class="small-title">
                TABLE GAME
            </span>

            <h1>Blackjack</h1>

            <p>
                Spela mot dealern och undersök hur regler,
                sannolikheter och house edge påverkar spelet.
            </p>

        </section>



        <!-- =====================================================
             GAME
        ====================================================== -->

        <section class="game-layout">


            <!-- =================================================
                 VÄNSTER: BLACKJACK
            ================================================== -->

            <div class="game-card">


                <div class="game-header">

                    <div>

                        <span class="label">
                            BLACKJACK TABLE
                        </span>

                        <h2>
                            Dealer
                        </h2>

                    </div>

                    <?php if ($game['active']): ?>

                        <span class="active">
                            IN PLAY
                        </span>

                    <?php else: ?>

                        <span class="inactive">
                            WAITING
                        </span>

                    <?php endif; ?>

                </div>



                <!-- DEALER -->

                <div class="hand-section">

                    <div class="hand-title">

                        <span>
                            Dealer
                        </span>

                        <?php if (!$game['active'] && count($game['dealer']) > 0): ?>

                            <strong>
                                <?php echo $dealerTotal; ?>
                            </strong>

                        <?php elseif (count($game['dealer']) > 0): ?>

                            <strong>
                                ?
                            </strong>

                        <?php endif; ?>

                    </div>


                    <div class="cards">

                        <?php foreach ($game['dealer'] as $index => $card): ?>

                            <?php

                            /*
                                Visa första dealer-kortet.
                                Det andra är dolt under aktivt spel.
                            */

                            $hidden =
                                $game['active']
                                && $index === 1;

                            ?>

                            <?php if ($hidden): ?>

                                <div class="card hidden-card">

                                    <span>?</span>

                                </div>

                            <?php else: ?>

                                <div
                                    class="
                                    card
                                    <?php
                                    echo (
                                        $card['suit'] === '♥'
                                        ||
                                        $card['suit'] === '♦'
                                    )
                                    ? 'red'
                                    : '';
                                    ?>
                                    "
                                >

                                    <span class="card-value">
                                        <?php echo $card['value']; ?>
                                    </span>

                                    <span class="card-suit">
                                        <?php echo $card['suit']; ?>
                                    </span>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                </div>



                <!-- PLAYER -->

                <div class="hand-section player-hand">

                    <div class="hand-title">

                        <span>
                            Du
                        </span>

                        <?php if (count($game['player']) > 0): ?>

                            <strong>
                                <?php echo $playerTotal; ?>
                            </strong>

                        <?php endif; ?>

                    </div>


                    <div class="cards">

                        <?php foreach ($game['player'] as $card): ?>

                            <div
                                class="
                                card
                                <?php
                                echo (
                                    $card['suit'] === '♥'
                                    ||
                                    $card['suit'] === '♦'
                                )
                                ? 'red'
                                : '';
                                ?>
                                "
                            >

                                <span class="card-value">
                                    <?php echo $card['value']; ?>
                                </span>

                                <span class="card-suit">
                                    <?php echo $card['suit']; ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>



                <!-- RESULT -->

                <?php if ($game['message'] !== ''): ?>

                    <div
                        class="
                        game-result
                        <?php
                        echo $game['result'];
                        ?>
                        "
                    >

                        <?php
                        echo htmlspecialchars(
                            $game['message']
                        );
                        ?>

                    </div>

                <?php endif; ?>



                <!-- CONTROLS -->

                <div class="controls">


                    <?php if (!$game['active']): ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="action"
                                value="new_game"
                            >

                            <div class="bet-input">

                                <label>
                                    Insats
                                </label>

                                <input
                                    type="number"
                                    name="bet"
                                    min="1"
                                    step="0.01"
                                    value="100"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                class="primary-button"
                            >
                                DEAL
                            </button>

                        </form>


                    <?php else: ?>


                        <div class="game-buttons">


                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="hit"
                                >

                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    HIT
                                </button>

                            </form>


                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="stand"
                                >

                                <button
                                    type="submit"
                                    class="secondary-button"
                                >
                                    STAND
                                </button>

                            </form>


                        </div>

                    <?php endif; ?>


                </div>


            </div>



            <!-- =================================================
                 HÖGER: INFORMATION
            ================================================== -->

            <aside class="information-card">


                <span class="small-title">
                    BLACKJACK THEORY
                </span>

                <h2>
                    Hur fungerar spelet?
                </h2>


                <div class="info-section">

                    <h3>
                        Målet
                    </h3>

                    <p>
                        Målet är att få en hand så nära
                        21 som möjligt utan att gå över 21.
                        Du spelar mot dealern, inte mot andra
                        spelare.
                    </p>

                </div>


                <div class="info-section">

                    <h3>
                        Kortens värde
                    </h3>

                    <p>
                        Nummerkorten är värda sitt nummer.
                        Knekt, dam och kung är värda 10.
                        Ess kan vara värt 1 eller 11.
                    </p>

                </div>


                <div class="info-section">

                    <h3>
                        Blackjack
                    </h3>

                    <p>
                        Blackjack innebär normalt att de två
                        första korten ger totalt 21:
                        ett ess och ett kort värt 10.
                    </p>

                    <div class="formula">
                        A + K = 21
                    </div>

                </div>


                <div class="info-section">

                    <h3>
                        Dealer
                    </h3>

                    <p>
                        I den här versionen måste dealern
                        dra kort tills handen når minst 17.
                        Därefter stannar dealern.
                    </p>

                </div>


                <div class="house-edge-box">

                    <strong>
                        House Edge
                    </strong>

                    <p>
                        Blackjack har ingen enda fast house edge.
                        Den påverkas bland annat av regler,
                        antal kortlekar och spelarens strategi.
                    </p>

                </div>


            </aside>


        </section>



        <!-- =====================================================
             HOUSE EDGE
        ====================================================== -->

        <section class="edge-section">


            <div class="section-heading">

                <span class="small-title">
                    HOUSE EDGE
                </span>

                <h2>
                    Varför har casinot en fördel?
                </h2>

                <p>
                    Blackjack är speciellt eftersom spelarens
                    beslut faktiskt påverkar resultatet.
                    Därför beror den statistiska fördelen
                    på vilka regler som används och hur
                    spelaren agerar.
                </p>

            </div>


            <div class="edge-grid">


                <div class="edge-card">

                    <span class="card-number">
                        01
                    </span>

                    <h3>
                        Spelarens beslut
                    </h3>

                    <p>
                        Till skillnad från exempelvis Dice
                        kan spelaren välja mellan olika
                        handlingar. Du kan exempelvis ta
                        ett nytt kort eller stanna.
                    </p>

                </div>


                <div class="edge-card">

                    <span class="card-number">
                        02
                    </span>

                    <h3>
                        Reglerna spelar roll
                    </h3>

                    <p>
                        Utbetalningen för Blackjack, när
                        dealern måste dra och hur ess hanteras
                        är exempel på regler som påverkar
                        spelets matematik.
                    </p>

                </div>


                <div class="edge-card">

                    <span class="card-number">
                        03
                    </span>

                    <h3>
                        Lång sikt
                    </h3>

                    <p>
                        Ett enskilt resultat säger väldigt
                        lite. När många händer spelas kan
                        det observerade resultatet jämföras
                        med den teoretiska sannolikheten.
                    </p>

                </div>


            </div>


        </section>



        <!-- =====================================================
             EXPECTED VALUE
        ====================================================== -->

        <section class="ev-section">


            <div>

                <span class="small-title">
                    EXPECTED VALUE
                </span>

                <h2>
                    Förväntat värde
                </h2>

                <p>
                    Expected Value beskriver det genomsnittliga
                    resultatet över många upprepningar av samma
                    situation.
                </p>

            </div>


            <div class="formula-large">

                EV = Σ (sannolikhet × utfall)

            </div>


        </section>


    </main>

</div>



<!-- =========================================================
     POPUP
========================================================== -->

<?php if ($game['result'] === 'money_error'): ?>

    <div
        class="popup-overlay"
        id="moneyPopup"
    >

        <div class="popup">

            <button
                class="popup-close"
                onclick="closePopup()"
            >
                ×
            </button>

            <div class="popup-icon">
                !
            </div>

            <h2>
                Inte tillräckligt med pengar
            </h2>

            <p>
                Din insats är större än ditt nuvarande saldo.
                Minska insatsen och försök igen.
            </p>

            <div class="popup-balance">

                Ditt saldo:

                <strong>
                    <?php
                    echo number_format(
                        $balance,
                        2,
                        ',',
                        ' '
                    );
                    ?>
                </strong>

            </div>

            <button
                class="popup-button"
                onclick="closePopup()"
            >
                Okej
            </button>

        </div>

    </div>

<?php endif; ?>



<script>

function closePopup()
{
    const popup = document.getElementById("moneyPopup");

    if (popup) {
        popup.remove();
    }
}

</script>


</body>

</html>