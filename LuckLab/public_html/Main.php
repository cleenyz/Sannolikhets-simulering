<?php
$base = '';
$title = 'Hem';
include 'includes/header.php';
?>

<main>

    <section class="hero">
        <div class="hero-text">
            <h1>Hur stor är chansen egentligen?</h1>
            <p>Vi jämför teoretisk sannolikhet med simuleringar av slumpspel. Allt spelas med låtsaspengar.</p>
            <a href="#games" class="button">Välj spel</a>
        </div>
    </section>

    <section id="games">
        <h2>Spel</h2>

        <div class="games">

            <!-- Byt bild: ändra filnamnet i url(...) -->
            <a class="game-card" href="games/coinflip.php" style="--img: url('assets/img/coinflip.jpg')">
                <h3>Coinflip</h3>
                <p>Klave eller krona. 50 % chans.</p>
                <span class="tag">Spela</span>
            </a>

            <div class="game-card soon" style="--img: url('assets/img/dice.jpg')">
                <h3>Dice</h3>
                <p>Sexsidig tärning.</p>
                <span class="tag">Kommer snart</span>
            </div>

            <div class="game-card soon" style="--img: url('assets/img/roulette.jpg')">
                <h3>Roulette</h3>
                <p>37 fack och husfördel.</p>
                <span class="tag">Kommer snart</span>
            </div>

            <div class="game-card soon" style="--img: url('assets/img/slots.jpg')">
                <h3>Slots</h3>
                <p>Slumpade kombinationer.</p>
                <span class="tag">Kommer snart</span>
            </div>

        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>
