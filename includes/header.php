<?php /* Före include på varje sida:  $base = '';  (eller '../' i games/)  och  $title = '...'; */ ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> – LuckLab</title>
    <link rel="stylesheet" href="<?= $base ?>style.css">
    <script src="<?= $base ?>js/app.js" defer></script>
</head>
<body>

<header class="navbar">
    <a class="logo" href="<?= $base ?>main.php">Luck<span>Lab</span></a>
    <nav class="nav-links">
        <a href="<?= $base ?>main.php">Hem</a>
        <a href="<?= $base ?>main.php#games">Spel</a>
    </nav>
    <div class="balance">Saldo <strong id="balance">10 000</strong></div>
</header>
