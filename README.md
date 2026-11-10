# LuckLab

Gymnasiearbete: hur stämmer sannolikhetsteori med simulerade slumpspel?

## Kör sajten

1. Installera PHP (eller XAMPP).
2. Öppna en terminal i den här mappen och kör: `php -S localhost:8000`
3. Öppna http://localhost:8000 i webbläsaren.

Får du felet "could not find driver": slå på `pdo_sqlite` i `php.ini`
(ta bort `;` framför `extension=pdo_sqlite`) och starta om.

## Filerna

| Fil | Vad den gör |
|---|---|
| `index.php` | Startsidan med spelkorten |
| `games/coinflip.php` | Coinflip-sidan: knappar, myntanimation, anropar servern |
| `simulation.php` | Simuleringssidan: väljer spel, visar tabell och graf |
| `lib/simulation.php` | Själva matten: teori (formler) och simulering (slumptal) för alla spel |
| `api/coinflip.php` | Slumpar myntet på servern, uppdaterar saldot, sparar omgången |
| `api/balance.php` | Ger saldot till menyn |
| `includes/header.php` | Meny och `<head>`, delas av alla sidor |
| `includes/footer.php` | Sidfot, delas av alla sidor |
| `includes/db.php` | Öppnar databasen och skapar tabellerna |
| `js/app.js` | Hämtar och visar saldot på alla sidor |
| `js/stats.js` | Live stats-panelen (resultat, omsatt, vinster/förluster, graf) som alla spel kan använda |
| `style.css` | Utseendet |
| `data/lucklab.sqlite` | Databasen (skapas automatiskt, finns inte i git) |

## Så hänger det ihop

**Coinflip:** knapptryck -> `fetch` till `api/coinflip.php` -> servern slumpar,
räknar saldot och sparar i databasen -> svaret kommer tillbaka -> myntet snurrar.
Slumpen ligger på servern, så spelaren kan inte påverka utfallet.

**Simulering:** `simulation.php` anropar `lib/simulation.php`, som gör två helt
separata saker per spel:
1. **Teori:** räknar ut väntevärdet med formler (EV = summan av sannolikhet x utfall).
2. **Simulering:** spelar spelet n gånger med slumptal.

Sedan jämförs de. De måste vara separata, annars jämför man något med sig självt.

## Databasen (SQLite, en fil)

| Tabell | Innehåll |
|---|---|
| `players` | En rad per webbläsare (nyckel i en cookie) och saldot |
| `rounds` | Varje spelad omgång: spel, insats, nettoresultat |
| `sim_runs` | Varje simuleringskörning: spel, n, seed, medelvärde, std.avv., teoretiskt EV |

Exempel på fråga (öppna filen i DB Browser for SQLite):

    SELECT game, COUNT(*) AS omgangar, SUM(net) / SUM(bet) AS observerat_ev
    FROM rounds GROUP BY game;

## Tre begrepp

- **Väntevärde (EV):** genomsnittligt resultat per spel i det långa loppet. Negativt EV = husfördel.
- **Standardavvikelse:** hur mycket enskilda spel varierar.
- **Seed:** startvärdet för slumptalen. Samma seed ger exakt samma simulering igen.
