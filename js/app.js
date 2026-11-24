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

// Knappen ↺ i menyn: sätter saldot till 10 000 igen
document.getElementById('reset-balance').onclick = async () => {
    if (!confirm('Återställa saldot till 10 000?')) return;      // fråga först
    const res = await fetch(BASE + 'api/reset.php', { method: 'POST' });  // be servern nollställa
    const data = await res.json();                                // svaret: {"balance": 10000}
    showBalance(data.balance);                                    // visa nya saldot
};
