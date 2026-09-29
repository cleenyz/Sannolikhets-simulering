<?php

session_start();

/*
|--------------------------------------------------------------------------
| STARTSALDO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 10000;
}

$balance = $_SESSION['balance'];


/*
|--------------------------------------------------------------------------
| STANDARDVÄRDEN
|--------------------------------------------------------------------------
*/

$bet = 100;
$target = 50;

$result = null;
$message = "";
$win = false;

$profit = 0;


/*
|--------------------------------------------------------------------------
| HOUSE EDGE
|--------------------------------------------------------------------------
|
| Casinots statistiska fördel.
|
| 0.01 = 1%
| 0.02 = 2%
|
*/

$houseEdge = 0.01;


/*
|--------------------------------------------------------------------------
| SPEL
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bet = floatval($_POST["bet"]);
    $target = floatval($_POST["target"]);


    /*
    |--------------------------------------------------------------------------
    | VALIDERA INSATS
    |--------------------------------------------------------------------------
    */

    if ($bet <= 0) {

        $message = "Insatsen måste vara större än 0.";

    }

    elseif ($bet > $balance) {

        $message = "Du har inte tillräckligt med saldo.";

    }

    elseif ($target <= 0 || $target >= 100) {

        $message = "Target måste vara mellan 0 och 100.";

    }

    else {

        /*
        |--------------------------------------------------------------------------
        | SANNOLIKHET
        |--------------------------------------------------------------------------
        |
        | Om Target = 50:
        |
        | 50 / 100 = 50%
        |
        */

        $winChance = $target / 100;


        /*
        |--------------------------------------------------------------------------
        | RÄTTVIS MULTIPLIER
        |--------------------------------------------------------------------------
        |
        | Om det inte fanns någon house edge:
        |
        | 100 / 50 = 2.00x
        |
        */

        $fairMultiplier = 1 / $winChance;


        /*
        |--------------------------------------------------------------------------
        | MULTIPLIER MED HOUSE EDGE
        |--------------------------------------------------------------------------
        |
        | 2.00 x 0.99 = 1.98x
        |
        */

        $multiplier = $fairMultiplier * (1 - $houseEdge);


        /*
        |--------------------------------------------------------------------------
        | RANDOM RESULTAT
        |--------------------------------------------------------------------------
        |
        | random_int ger ett slumpmässigt heltal.
        |
        | 0 - 10000 blir sedan 0.00 - 100.00
        |
        */

        $result = random_int(0, 10000) / 100;


        /*
        |--------------------------------------------------------------------------
        | VINST ELLER FÖRLUST
        |--------------------------------------------------------------------------
        */

        if ($result < $target) {

            $win = true;

            /*
            | Nettoresultat.
            |
            | Exempel:
            |
            | Bet = 100
            | Multiplier = 1.98
            |
            | Vinst = +98
            */

            $profit = $bet * ($multiplier - 1);

            $balance += $profit;

            $message = "VINST";

        }

        else {

            $win = false;

            $profit = -$bet;

            $balance -= $bet;

            $message = "FÖRLUST";
        }


        /*
        |--------------------------------------------------------------------------
        | SPARA NYTT SALDO
        |--------------------------------------------------------------------------
        */

        $_SESSION["balance"] = $balance;
    }
}


/*
|--------------------------------------------------------------------------
| MATEMATIK SOM VISAS INNAN SPELET
|--------------------------------------------------------------------------
*/

$winChance = $target / 100;

$fairMultiplier = 1 / $winChance;

$multiplier = $fairMultiplier * (1 - $houseEdge);


/*
|--------------------------------------------------------------------------
| FÖRVÄNTAT VÄRDE
|--------------------------------------------------------------------------
|
| För varje 100 kr som spelas:
|
| 99 kr förväntas tillbaka om house edge = 1%.
|
*/

$expectedReturn = 1 - $houseEdge;

?>

<!DOCTYPE html>

<html lang="sv">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dice | Probability Lab</title>

    <link
        rel="stylesheet"
        href="../css/dice.css"
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
                <?php echo number_format($balance, 2, ',', ' '); ?>
            </strong>

        </div>

    </header>



    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="container">


        <!-- HEADER -->

        <section class="page-header">

            <div>

                <span class="small-title">
                    SIMULATION GAME
                </span>

                <h1>Dice</h1>

                <p>
                    Undersök hur sannolikhet och house edge påverkar
                    resultatet över tid.
                </p>

            </div>

        </section>



        <!-- =====================================================
             GAME
        ====================================================== -->

        <section class="game-layout">


            <!-- VÄNSTER: SPELET -->

            <div class="game-card">


                <div class="game-card-header">

                    <div>

                        <span class="label">
                            DICE ROLL
                        </span>

                        <h2>Roll under target</h2>

                    </div>

                    <div class="status">
                        LIVE
                    </div>

                </div>



                <!-- RESULTAT -->

                <div class="roll-area">

                    <div
                        id="rollNumber"
                        class="roll-number"
                    >

                        <?php

                        if ($result !== null) {

                            echo number_format($result, 2);

                        } else {

                            echo "50.00";

                        }

                        ?>

                    </div>


                    <?php if ($result !== null): ?>

                        <div
                            class="result-message
                            <?php echo $win ? 'win' : 'lose'; ?>"
                        >

                            <?php echo $message; ?>

                        </div>

                    <?php else: ?>

                        <div class="result-message">
                            Ready to roll
                        </div>

                    <?php endif; ?>

                </div>



                <!-- FORM -->

                <form
                    method="POST"
                    id="diceForm"
                >


                    <!-- INSATS -->

                    <div class="input-row">


                        <div class="input-group">

                            <label for="bet">
                                Insats
                            </label>

                            <div class="input-wrapper">

                                <input
                                    type="number"
                                    id="bet"
                                    name="bet"
                                    value="<?php echo $bet; ?>"
                                    min="1"
                                    step="0.01"
                                    required
                                >

                                <span>
                                    credits
                                </span>

                            </div>

                        </div>



                        <!-- TARGET -->

                        <div class="input-group">

                            <label for="target">
                                Target
                            </label>

                            <div class="input-wrapper">

                                <input
                                    type="number"
                                    id="target"
                                    name="target"
                                    value="<?php echo $target; ?>"
                                    min="0.01"
                                    max="99.99"
                                    step="0.01"
                                    required
                                >

                                <span>
                                    %
                                </span>

                            </div>

                        </div>


                    </div>



                    <!-- STATISTIK -->

                    <div class="stats-grid">


                        <div class="stat">

                            <span>
                                Vinstchans
                            </span>

                            <strong>
                                <?php
                                echo number_format(
                                    $winChance * 100,
                                    2
                                );
                                ?>%
                            </strong>

                        </div>



                        <div class="stat">

                            <span>
                                Fair multiplier
                            </span>

                            <strong>
                                <?php
                                echo number_format(
                                    $fairMultiplier,
                                    2
                                );
                                ?>x
                            </strong>

                        </div>



                        <div class="stat">

                            <span>
                                Actual multiplier
                            </span>

                            <strong>
                                <?php
                                echo number_format(
                                    $multiplier,
                                    2
                                );
                                ?>x
                            </strong>

                        </div>



                        <div class="stat">

                            <span>
                                House edge
                            </span>

                            <strong class="edge">
                                <?php
                                echo number_format(
                                    $houseEdge * 100,
                                    2
                                );
                                ?>%
                            </strong>

                        </div>


                    </div>



                    <!-- KNAPP -->

                    <button
                        type="submit"
                        class="roll-button"
                        id="rollButton"
                    >

                        ROLL

                    </button>


                </form>

            </div>



            <!-- =================================================
                 HÖGER: MATEMATIK
            ================================================== -->

            <aside class="information-card">


                <div class="info-title">

                    <span class="small-title">
                        MATHEMATICS
                    </span>

                    <h2>Hur fungerar spelet?</h2>

                </div>


                <div class="info-section">

                    <h3>1. Vinstchans</h3>

                    <p>
                        Target bestämmer hur stor sannolikheten
                        är att du vinner.
                    </p>

                    <div class="formula">

                        Target / 100

                    </div>

                    <p class="example">

                        Exempel: Target 50 innebär
                        50 / 100 = 50% vinstchans.

                    </p>

                </div>



                <div class="info-section">

                    <h3>2. Fair multiplier</h3>

                    <p>
                        Om spelet inte hade någon house edge
                        skulle multiplern vara:
                    </p>

                    <div class="formula">

                        100 / Target

                    </div>

                    <p class="example">

                        Target 50 →
                        100 / 50 = 2.00x

                    </p>

                </div>



                <div class="info-section">

                    <h3>3. House edge</h3>

                    <p>
                        Casinot kan lägga in en matematisk
                        fördel genom att minska multiplern.
                    </p>

                    <div class="formula">

                        Fair multiplier × (1 - house edge)

                    </div>

                    <p class="example">

                        2.00 × 0.99 = 1.98x

                    </p>

                </div>



                <div class="house-edge-box">

                    <div class="edge-number">
                        <?php
                        echo number_format(
                            $houseEdge * 100,
                            2
                        );
                        ?>%
                    </div>

                    <div>

                        <strong>
                            House Edge
                        </strong>

                        <p>
                            Detta är den teoretiska fördelen
                            som spelet har på lång sikt.
                        </p>

                    </div>

                </div>

            </aside>


        </section>



        <!-- =====================================================
             VARFÖR CASINOT HAR EDGE
        ====================================================== -->

        <section class="edge-section">


            <div class="section-heading">

                <span class="small-title">
                    PROBABILITY THEORY
                </span>

                <h2>Varför kan casinot ha en statistisk fördel?</h2>

                <p>
                    Det handlar inte om att casinot måste vinna
                    varje enskild omgång. Fördelen kommer från
                    matematiken över många spel.
                </p>

            </div>



            <div class="edge-grid">


                <div class="edge-card">

                    <div class="card-number">
                        01
                    </div>

                    <h3>Expected Value</h3>

                    <p>
                        Expected Value, eller förväntat värde,
                        beskriver det genomsnittliga resultatet
                        om samma spel upprepas väldigt många gånger.
                    </p>

                    <div class="formula">

                        EV = Σ (sannolikhet × utfall)

                    </div>

                </div>



                <div class="edge-card">

                    <div class="card-number">
                        02
                    </div>

                    <h3>House Edge</h3>

                    <p>
                        Om house edge är 1% betyder det förenklat
                        att det teoretiska förväntade resultatet
                        är -1% för spelaren över mycket många spel.
                    </p>

                    <div class="formula">

                        EV ≈ -1% per satsad enhet

                    </div>

                </div>



                <div class="edge-card">

                    <div class="card-number">
                        03
                    </div>

                    <h3>Stora datamängder</h3>

                    <p>
                        På några få spel kan resultatet avvika
                        kraftigt från teorin. När antalet
                        simuleringar ökar tenderar det observerade
                        resultatet att närma sig den teoretiska
                        sannolikheten.
                    </p>

                    <div class="formula">

                        n → ∞

                        <br>

                        observerad sannolikhet → teoretisk sannolikhet

                    </div>

                </div>


            </div>


        </section>



        <!-- =====================================================
             EXEMPEL
        ====================================================== -->

        <section class="example-section">

            <div>

                <span class="small-title">
                    EXEMPEL
                </span>

                <h2>
                    10 000 spel
                </h2>

                <p>
                    Anta att en spelare satsar 100 credits
                    varje gång och att spelet har 1% house edge.
                </p>

            </div>


            <div class="example-result">

                <div>

                    <span>Total satsning</span>

                    <strong>
                        1 000 000 credits
                    </strong>

                </div>


                <div>

                    <span>Teoretiskt EV</span>

                    <strong class="negative">
                        -10 000 credits
                    </strong>

                </div>

            </div>

        </section>


    </main>

</div>



<!-- =========================================================
     ANIMATION
========================================================== -->

<script>

const form = document.getElementById("diceForm");
const button = document.getElementById("rollButton");
const number = document.getElementById("rollNumber");

form.addEventListener("submit", function(event) {

    /*
        Förhindra direkt reload.
    */
    event.preventDefault();

    /*
        Gör knappen obrukbar under animationen.
    */
    button.disabled = true;

    button.innerText = "ROLLING...";

    /*
        Startvärde.
    */
    let counter = 0;

    /*
        Hur snabbt siffrorna ändras.
    */
    const animation = setInterval(function() {

        const randomNumber =
            (Math.random() * 100).toFixed(2);

        number.innerText = randomNumber;

        counter++;

        /*
            Efter ungefär 1 sekund skickas formuläret.
        */
        if (counter >= 20) {

            clearInterval(animation);

            form.submit();
        }

    }, 50);

});

</script>


</body>

</html>