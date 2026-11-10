// js/stats.js – Live stats för spelsidorna (ungefär som på Stake).
//
// Användning på en spelsida:
//   <aside class="live-stats" id="live-stats"></aside>
//   const live = new LiveStats(document.getElementById('live-stats'), { key: 'coinflip' });
//   live.add(bet, net);     // efter varje spel. net = +vinst eller -förlust
//
// Statistiken gäller sessionen (sparas i webbläsarfliken, "Återställ" nollställer).


// ---- Räkna ut statistiken (ingen HTML här, bara matte) ----

function summarize(bets) {
    let profit = 0, wagered = 0, wins = 0, losses = 0;
    const cum = [0];                       // resultat efter 0, 1, 2, ... spel

    for (const b of bets) {
        profit += b.net;
        wagered += b.bet;
        if (b.net > 0) wins++;
        if (b.net < 0) losses++;
        cum.push(profit);
    }

    return { profit: profit, wagered: wagered, count: bets.length, wins: wins, losses: losses, cum: cum };
}


// ---- Grafen: gör om resultatet efter varje spel till en SVG-linje ----

function chartSvg(cum) {
    const W = 400, H = 160, PAD = 12;

    let min = 0, max = 0;                  // noll ska alltid synas
    for (const v of cum) {
        if (v < min) min = v;
        if (v > max) max = v;
    }
    if (max === min) { max = 1; min = -1; }

    const y = v => PAD + (max - v) / (max - min) * (H - 2 * PAD);
    const x = i => i / (cum.length - 1) * W;

    const y0 = y(0).toFixed(1);
    const zero = '<line class="ls-zero" x1="0" y1="' + y0 + '" x2="' + W + '" y2="' + y0 + '"/>';

    if (cum.length < 2) return zero;

    const pts = cum.map((v, i) => x(i).toFixed(1) + ',' + y(v).toFixed(1)).join(' ');
    const up = cum[cum.length - 1] >= 0;

    return zero +
        '<g class="' + (up ? 'ls-up' : 'ls-down') + '">' +
        '<polygon class="ls-area" points="0,' + y0 + ' ' + pts + ' ' + W + ',' + y0 + '"/>' +
        '<polyline class="ls-line" points="' + pts + '"/>' +
        '</g>';
}


function fmt(n) {
    return (Math.round(n * 100) / 100).toLocaleString('sv-SE');
}


// ---- Panelen ----

class LiveStats {
    constructor(el, options) {
        this.el = el;
        this.key = 'livestats:' + ((options && options.key) || 'default');
        this.bets = this.load();

        this.el.innerHTML =
            '<div class="ls-head">' +
                '<h3>Live stats</h3>' +
                '<button type="button" class="ls-reset">Återställ</button>' +
            '</div>' +
            '<div class="ls-grid">' +
                '<div class="ls-tile ls-wide"><span>Resultat</span><strong data-k="profit">0</strong></div>' +
                '<div class="ls-tile"><span>Omsatt</span><strong data-k="wagered">0</strong></div>' +
                '<div class="ls-tile"><span>Antal spel</span><strong data-k="count">0</strong></div>' +
                '<div class="ls-tile"><span>Vinster</span><strong data-k="wins" class="ls-pos">0</strong></div>' +
                '<div class="ls-tile"><span>Förluster</span><strong data-k="losses" class="ls-neg">0</strong></div>' +
            '</div>' +
            '<div class="ls-chart">' +
                '<svg viewBox="0 0 400 160" preserveAspectRatio="none"></svg>' +
                '<p class="ls-empty">Spela för att se grafen</p>' +
            '</div>';

        this.el.querySelector('.ls-reset').onclick = () => this.reset();
        this.update();
    }

    load() {
        try {
            const saved = JSON.parse(sessionStorage.getItem(this.key));
            return Array.isArray(saved) ? saved : [];
        } catch (e) {
            return [];
        }
    }

    save() {
        try { sessionStorage.setItem(this.key, JSON.stringify(this.bets)); } catch (e) { }
    }

    add(bet, net) {
        this.bets.push({ bet: bet, net: net });
        this.save();
        this.update();
    }

    reset() {
        this.bets = [];
        this.save();
        this.update();
    }

    update() {
        const s = summarize(this.bets);
        const set = (k, text) => { this.el.querySelector('[data-k="' + k + '"]').textContent = text; };

        set('profit', (s.profit > 0 ? '+' : '') + fmt(s.profit));
        set('wagered', fmt(s.wagered));
        set('count', s.count.toLocaleString('sv-SE'));
        set('wins', s.wins.toLocaleString('sv-SE'));
        set('losses', s.losses.toLocaleString('sv-SE'));

        const profit = this.el.querySelector('[data-k="profit"]');
        profit.className = s.profit > 0 ? 'ls-pos' : (s.profit < 0 ? 'ls-neg' : '');

        this.el.querySelector('svg').innerHTML = chartSvg(s.cum);
        this.el.querySelector('.ls-empty').hidden = s.count > 0;
    }
}
