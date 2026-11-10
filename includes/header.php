<?php
/*
    Visas överst på varje sida.
    Sidan som inkluderar filen sätter först:
        $base  = '';     (sidor i rotmappen)  eller  '../'  (sidor i games/)
        $title = 'Sidans namn';
*/
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> – LuckLab</title>
    <link rel="stylesheet" href="<?= $base ?>style.css">

    <!-- BASE berättar för JavaScript var sajtens rotmapp ligger -->
    <script>const BASE = '<?= $base ?>';</script>
    <script src="<?= $base ?>js/app.js" defer></script>
</head>
<body>

<header class="navbar">
    <a class="logo" href="<?= $base ?>index.php">Luck<span>Lab</span></a>
    <nav class="nav-links">
        <a href="<?= $base ?>index.php">Hem</a>
        <a href="<?= $base ?>index.php#games">Spel</a>
        <a href="<?= $base ?>simulation.php">Simulering</a>
    </nav>
    <div class="balance">Saldo <strong id="balance">–</strong></div>
</header>
