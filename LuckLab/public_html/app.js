// js/app.js – laddas på alla sidor.
// Saldot sparas i webbläsaren (localStorage) så det är samma på alla sidor.
// Senare flyttar vi det till SQLite.

function getBalance() {
    const saved = localStorage.getItem('balance');
    return saved === null ? 10000 : Number(saved);
}

function setBalance(amount) {
    localStorage.setItem('balance', amount);
    showBalance();
}

function showBalance() {
    document.getElementById('balance').textContent = getBalance().toLocaleString('sv-SE');
}

showBalance();
