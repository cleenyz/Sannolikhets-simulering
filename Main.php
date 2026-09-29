<?php
// main.php
?>

<!DOCTYPE html>
<html lang="sv">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LuckLab</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- NAVIGATION -->

    <nav class="navbar">

        <div class="logo">
            Luck<span>Lab</span>
        </div>

        <div class="nav-links">
            <a href="main.php" class="active">Home</a>
            <a href="#games">Games</a>
            <a href="#statistics">Statistics</a>
        </div>

        <div class="balance">
            Balance: <strong>10,000</strong>
        </div>

    </nav>


    <!-- MAIN CONTENT -->

    <main>

        <!-- INTRO -->

        <section class="hero">

            <p class="small-title">
                PROBABILITY PROJECT
            </p>

            <h1>
                Welcome to <span>LuckLab</span>
            </h1>

            <p>
                Test different games based on probability and randomness.
                All games use virtual currency and can later be used
                for mathematical simulations.
            </p>

            <a href="#games" class="button">
                Explore Games
            </a>

        </section>


        <!-- GAMES -->

        <section id="games" class="section">

            <div class="section-title">

                <div>
                    <p class="small-title">AVAILABLE GAMES</p>
                    <h2>Choose a game</h2>
                </div>

            </div>


            <div class="games">


                <!-- COINFLIP -->

                <div class="game-card">

                    <div class="game-icon">
                        50/50
                    </div>

                    <h3>Coinflip</h3>

                    <p>
                        Test a 50/50 probability event.
                    </p>

                    <a href="games/coinflip.php" class="play-button">
                        Play
                    </a>

                </div>


                <!-- DICE -->

                <div class="game-card">

                    <div class="game-icon dice">
                        6
                    </div>

                    <h3>Dice</h3>

                    <p>
                        Roll a six-sided dice and explore
                        different probabilities.
                    </p>

                    <a href="games/dice.php" class="play-button">
                        Play
                    </a>

                </div>


                <!-- ROULETTE -->

                <div class="game-card">

                    <div class="game-icon roulette">
                        0
                    </div>

                    <h3>Roulette</h3>

                    <p>
                        Simulate roulette outcomes and
                        calculate their probabilities.
                    </p>

                    <a href="games/roulette.php" class="play-button">
                        Play
                    </a>

                </div>


                <!-- SLOTS -->

                <div class="game-card">

                    <div class="game-icon slots">
                        777
                    </div>

                    <h3>Slots</h3>

                    <p>
                        Generate random combinations and
                        investigate their probability.
                    </p>

                    <a href="games/slots.php" class="play-button">
                        Play
                    </a>

                </div>


                <!-- BLACKJACK -->

                <div class="game-card">

                    <div class="game-icon blackjack">
                        21
                    </div>

                    <h3>Blackjack</h3>

                    <p>
                        Play against the dealer and explore
                        probability, strategy and house edge.
                    </p>

                    <a href="games/blackjack.php" class="play-button">
                        Play
                    </a>

                </div>


            </div>

        </section>


        <!-- PROJECT / STATISTICS -->

        <section id="statistics" class="statistics">

            <div>

                <p class="small-title">
                    OUR PROJECT
                </p>

                <h2>
                    Probability & Simulation
                </h2>

                <p>
                    The purpose of this project is to investigate
                    how theoretical probabilities compare with
                    results from computer simulations.
                </p>

            </div>

            <div class="stat-box">

                <div>
                    <strong>5</strong>
                    <span>Games</span>
                </div>

                <div>
                    <strong>0</strong>
                    <span>Simulations</span>
                </div>

                <div>
                    <strong>100%</strong>
                    <span>Virtual</span>
                </div>

            </div>

        </section>

    </main>


    <!-- FOOTER -->

    <footer>

        <p>
            LuckLab — Gymnasiearbete
        </p>

        <p>
            Probability & Simulation
        </p>

    </footer>

</body>
</html>