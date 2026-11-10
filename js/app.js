// js/app.js – laddas på alla sidor.
// Saldot ligger i databasen (servern). Webbläsaren frågar api/balance.php.
// BASE ('' eller '../') sätts i includes/header.php.

function showBalance(amount) {
    document.getElementById('balance').textContent =
        Number(amount).toLocaleString('sv-SE');
}

async function loadBalance() {
    try {
        const res = await fetch(BASE + 'api/balance.php');
        const data = await res.json();
        showBalance(data.balance);
    } catch (e) {
        document.getElementById('balance').textContent = '–';
    }
}

loadBalance();
