<script>
/** Cash memo — shared by the TradeERP POS and the ServiceERP New Bill screens.
 * Voice first: tap the mic, say "item, quantity, rate" (then "next"), and rows fill in live. Typing is the fallback.
 * The host Alpine component spreads this in (`...cashMemoMixin()`) and supplies four hooks:
 *   memoItems()                          -> [{id, name, price, gst, stock}]  (stock = number, or null when not tracked)
 *   memoPushCatalog(item, qty, rate)     -> add/merge a catalogue line into the host's cart
 *   memoPushFree(name, qty, rate, gst)   -> add a free (not-in-list) line to the host's cart
 *   memoDone()                           -> e.g. switch to the cart tab */
function cashMemoMixin() {
  const UNITS = { zero:0, one:1, two:2, three:3, four:4, five:5, six:6, seven:7, eight:8, nine:9, ten:10, eleven:11, twelve:12, thirteen:13, fourteen:14, fifteen:15, sixteen:16, seventeen:17, eighteen:18, nineteen:19 };
  const TENS = { twenty:20, thirty:30, forty:40, fifty:50, sixty:60, seventy:70, eighty:80, ninety:90 };
  /** "two thousand five hundred" -> "2500" (speech engines return small English numbers as words). */
  function wordsToDigits(text) {
    const toks = text.split(/\s+/); const out = []; let i = 0;
    const isNum = w => w in UNITS || w in TENS || w === 'hundred' || w === 'thousand' || w === 'lakh' || w === 'lac';
    while (i < toks.length) {
      const w = toks[i].toLowerCase().replace(/[.,]$/, '');
      if (!isNum(w)) { out.push(toks[i]); i++; continue; }
      let total = 0, cur = 0;
      while (i < toks.length) {
        const x = toks[i].toLowerCase().replace(/[.,]$/, '');
        if (x in UNITS) cur += UNITS[x];
        else if (x in TENS) cur += TENS[x];
        else if (x === 'hundred') cur = (cur || 1) * 100;
        else if (x === 'thousand') { total += (cur || 1) * 1000; cur = 0; }
        else if (x === 'lakh' || x === 'lac') { total += (cur || 1) * 100000; cur = 0; }
        else if (x === 'and' && i + 1 < toks.length && isNum(toks[i + 1].toLowerCase())) { /* "two hundred and fifty" */ }
        else break;
        i++;
      }
      out.push(String(total + cur));
    }
    return out.join(' ');
  }

  // Short forms are understood too: "q 2", "qty 2", "2 pc", "2 kg" for quantity; "r 50", "rs 50", "@ 50" for rate.
  const QTY_WORDS = 'quantity|qty|qt|q|pieces?|pcs|pc|nos';
  const UNIT_WORDS = 'kgs?|kilos?|kilograms?|gms?|g|grams?|grammes?|ltrs?|litres?|liters?|ml|mls|dozen|dz|packets?|pkt|box(?:es)?|bags?|strips?|sets?|meters?|metres?|mtrs?|cm|ft|feet';
  // Measuring units. Weight, volume and length convert to a base unit (the rate is "per kg", "per ltr", "per m"); counted units stand alone.
  const UNIT_DEF = {
    pcs: { base: 'pcs', f: 1 }, dozen: { base: 'dozen', f: 1 }, box: { base: 'box', f: 1 }, pkt: { base: 'pkt', f: 1 }, bag: { base: 'bag', f: 1 }, strip: { base: 'strip', f: 1 }, set: { base: 'set', f: 1 },
    kg: { base: 'kg', f: 1 }, g: { base: 'kg', f: 0.001 }, ltr: { base: 'ltr', f: 1 }, ml: { base: 'ltr', f: 0.001 },
    m: { base: 'm', f: 1 }, cm: { base: 'm', f: 0.01 }, ft: { base: 'm', f: 0.3048 },
  };
  const UNIT_ALIAS = { pc: 'pcs', pcs: 'pcs', piece: 'pcs', pieces: 'pcs', nos: 'pcs', no: 'pcs', unit: 'pcs', units: 'pcs', dozen: 'dozen', dz: 'dozen', box: 'box', boxes: 'box', pkt: 'pkt', packet: 'pkt', packets: 'pkt', pack: 'pkt',
    bag: 'bag', bags: 'bag', strip: 'strip', strips: 'strip', set: 'set', sets: 'set',
    kg: 'kg', kgs: 'kg', kilo: 'kg', kilos: 'kg', kilogram: 'kg', kilograms: 'kg', g: 'g', gm: 'g', gms: 'g', gram: 'g', grams: 'g', gramme: 'g', grammes: 'g',
    ltr: 'ltr', ltrs: 'ltr', l: 'ltr', lt: 'ltr', litre: 'ltr', litres: 'ltr', liter: 'ltr', liters: 'ltr', ml: 'ml', mls: 'ml',
    m: 'm', mtr: 'm', mtrs: 'm', meter: 'm', meters: 'm', metre: 'm', metres: 'm', cm: 'cm', ft: 'ft', feet: 'ft', foot: 'ft' };
  const normUnit = w => UNIT_ALIAS[String(w || '').trim().toLowerCase().replace(/\.$/, '')] || null;
  const RATE_WORDS = 'rate|price|rs|rupees?|rupee|each|r|at';
  const HOMO = { to: 2, too: 2, tu: 2, won: 1, tree: 3, free: 3, fore: 4, for: 4, fife: 5, ate: 8 };
  /** Engines often glue the short words to their numbers ("Q2 R50", "2kg", "₹50") — split them apart first. */
  const splitGlued = t => t.replace(/\b(q|qt|qty|r|rs|rate|price|at)[.:]?(?=\d)/gi, "$1 ").replace(/(\d)(?=(?:kgs?|gms?|g|ltrs?|litres?|liters?|pcs?|nos|dozen|dz|pkt|packets?|boxe?s?|bags?)\b)/gi, "$1 ").replace(/([₹@])(?=\d)/g, "$1 ");
  const HOMO_RE = 'to|too|tu|won|tree|free|fore|for|fife|ate';

  return {
    leftTab: 'stock',
    memoRows: [], memoSeq: 0, memoText: '', memoMsg: '', voiceError: '', voiceLang: 'en-IN',
    memoErr: '', lastSaid: '', lastSaidAt: 0, lastK: 0, listening: false, wantListening: false, rec: null, interim: '', heard: [], typeOpen: false,
    voiceSupported: !!(window.SpeechRecognition || window.webkitSpeechRecognition),

    memoInit() { this.memoAddRows(5); if (!this.voiceSupported) this.typeOpen = true; },
    blankRow() { return { k: ++this.memoSeq, item: '', qty: '', rate: '', pid: null, gst: 18, stock: null, error: '', guess: null, step: 0, unit: 'pcs' }; },
    unitInfo(r) { return UNIT_DEF[r.unit] || UNIT_DEF.pcs; },
    // Quantity in the unit the rate is quoted in (500 g at a per-kg rate -> 0.5).
    baseQty(r) { const q = Number(r.qty) || (r.item.trim() ? 1 : 0); return Math.round(q * this.unitInfo(r).f * 1000) / 1000; },
    memoAddRows(n) { for (let i = 0; i < n; i++) this.memoRows.push(this.blankRow()); },
    memoRemove(i) { this.memoRows.splice(i, 1); if (this.memoRows.length === 0) this.memoAddRows(1); },
    memoClear() { this.memoRows = []; this.memoAddRows(5); this.memoMsg = ''; this.heard = []; },
    memoAmount(r) { return this.baseQty(r) * (Number(r.rate) || 0); },
    memoTotal() { return this.memoRows.reduce((s, r) => s + this.memoAmount(r), 0); },
    memoEnter(i) {
      if (i === this.memoRows.length - 1) this.memoAddRows(1);
      this.$nextTick(() => { const boxes = document.querySelectorAll('[data-memo-item]'); if (boxes[i + 1]) boxes[i + 1].focus(); });
    },
    // Exact name (from the suggestions) -> linked to the catalogue item with its rate and GST; anything else stays a free item.
    matchRow(r) {
      const name = r.item.trim().toLowerCase();
      const p = name ? this.memoItems().find(x => x.name.toLowerCase() === name) : null;
      if (p) { r.pid = p.id; r.gst = p.gst; r.stock = p.stock; if (r.rate === '' || r.rate === null) r.rate = p.price; if (r.unit === 'pcs' && normUnit(p.unit)) r.unit = normUnit(p.unit); }
      else { r.pid = null; r.stock = null; }
      r.error = '';
    },
    // Every spoken/typed word must appear in the catalogue name; the shortest such name wins.
    bestMatch(text) {
      const words = text.toLowerCase().split(/\s+/).filter(w => w.length > 1);
      if (!words.length) return null;
      let best = null;
      for (const p of this.memoItems()) {
        const n = p.name.toLowerCase();
        if (words.every(w => n.includes(w)) && (!best || p.name.length < best.name.length)) best = p;
      }
      return best;
    },
    // "Basin mixer 2 2500, granite 10 180" or "basin mixer two twenty five hundred next ..." -> rows. First number = quantity, second = rate.
    parseChunk(raw) {
      raw = splitGlued(raw);
      const filler = new RegExp('\\b(' + QTY_WORDS + '|' + UNIT_WORDS + '|' + RATE_WORDS + '|number|of|x|for|per)\\b|@|₹', 'gi');
      // Speech engines often hear "two" as "to/too" and "four" as "for" right after the words quantity / rate.
      const fixed = raw.replace(new RegExp('\\b(' + QTY_WORDS + '|' + RATE_WORDS + ')\\s+(' + HOMO_RE + ')\\b', 'gi'), (m, k, w) => k + ' ' + HOMO[w.toLowerCase()]);
      const text = wordsToDigits(fixed);
      const segs = text.replace(/\b(next|then|aur|and next)\b/gi, '\n').split(/[\n;,]+/).map(t => t.trim()).filter(Boolean);
      let added = 0;
      for (const seg of segs) {
        const NUM = /(?<![\w.])\d+(?:\.\d+)?(?![\w])/g; // standalone numbers only, so "10000mAh" stays part of the name
        // The spoken labels decide which number is which: "quantity 2", "rate 50", "2 pieces", "50 rupees".
        const hasQ = new RegExp('\\b(' + QTY_WORDS + ')\\b', 'i').test(seg);
        const qBefore = new RegExp('^(quantity|qty|qt|q)$'), qAfter = new RegExp('^(' + QTY_WORDS.replace('|q|', '|') + '|' + UNIT_WORDS + ')$', 'i');
        const rBefore = /^(rate|price|rs|rupees?|at|r|@|₹)$/, rAfter = /^(rupees?|rs|each)$/;
        let qty, rate, unit; const loose = [];
        for (const m of seg.matchAll(NUM)) {
          const before = (seg.slice(0, m.index).match(/(\S+)\s*$/) || [, ''])[1].toLowerCase();
          const after = (seg.slice(m.index + m[0].length).match(/^\s*(\S+)/) || [, ''])[1].toLowerCase();
          const v = Number(m[0]);
          if (!unit && normUnit(after)) unit = normUnit(after);      // "500 gram", "2 kg", "3 dozen"
          if (qBefore.test(before) || qAfter.test(after)) { if (qty === undefined) qty = v; else loose.push(v); }
          else if (rBefore.test(before) || (rAfter.test(after) && !/^\s*\S+\s+[\d₹]/.test(seg.slice(m.index + m[0].length)))) { if (rate === undefined) rate = v; else loose.push(v); }
          else loose.push(v);
        }
        // A label was spoken but its number was not heard ("quantity date 50"): the lone number is then the other value.
        // One lone number of 100+ ( "biscuit 610" ) is far more likely a rate than a quantity.
        let joined;
        if (qty === undefined && rate === undefined && !hasQ && loose.length === 1 && loose[0] >= 100) joined = loose.shift();
        if (qty === undefined && !hasQ) qty = loose.shift();
        if (rate === undefined) rate = loose.shift();
        const nums = [qty === undefined ? 1 : qty, rate];
        let name = seg.replace(/\b(quantity|qty)\s+[a-z]+(?=\s+\d)/gi, ' ').replace(NUM, ' ').replace(filler, ' ');
        name = name.replace(/\.(?=\s|$)/g, ' ').replace(/[:;!?"'`()\[\]{}\-–—_*#\/\\|=+<>~^%&$]+/g, ' ').replace(/\s+/g, ' ').trim();
        if (!/\p{L}/u.test(name)) continue;          // stray punctuation / noise is not an item
        const row = this.blankRow();
        const p = this.bestMatch(name);
        row.item = p ? p.name : name.replace(/\b\w/g, c => c.toUpperCase());
        if (p) { row.pid = p.id; row.gst = p.gst; row.stock = p.stock; }
        if (joined !== undefined) {
          const sp = this.splitJoined(joined, p ? p.price : null);
          if (sp) { qty = sp.qty; rate = sp.rate; row.guess = joined; } else { rate = joined; }
          nums[0] = qty === undefined ? 1 : qty; nums[1] = rate;
        }
        row.qty = nums[0] > 0 ? nums[0] : 1;
        row.unit = unit || (p && normUnit(p.unit)) || 'pcs';
        row.rate = nums[1] !== undefined ? nums[1] : (p ? p.price : '');
        // step = what the next spoken number fills: 0 quantity, 1 rate, 2 nothing (row complete).
        row.step = rate !== undefined ? 2 : (qty !== undefined ? 1 : 0);
        this.lastK = row.k;
        const blank = this.memoRows.findIndex(r => r.item.trim() === '' && r.qty === '' && r.rate === '');
        if (blank >= 0) this.memoRows.splice(blank, 1, row); else this.memoRows.push(row);
        added++;
      }
      if (added) this.heard.push(raw.trim());
      return added;
    },
    parseMemoText() {
      if (this.listening) this.stopVoice();
      const text = this.memoText.trim();
      if (!text) { this.memoMsg = 'Tap the mic and speak, or type an item first.'; return; }
      const n = this.parseChunk(text);
      if (n) { this.memoText = ''; this.memoMsg = 'Added: "' + text + '" — speak the next item, or press Add to cart when done.'; }
      else this.memoMsg = 'Could not understand "' + text + '" — edit it in the box like: Coffee 2 50';
    },
    // The engine often joins "6 ... 10" into "610". Split it back: by the item's saved price when it fits, else 3 digits = 1+2, 4 digits = 2+2.
    splitJoined(n, price) {
      const s = String(n);
      if (!Number.isInteger(n) || s.length < 3) return null;
      if (price) {
        const ps = String(Math.round(price));
        if (s.length > ps.length && s.endsWith(ps)) return { qty: Number(s.slice(0, -ps.length)), rate: price };
      }
      if (s.length > 4) return null;
      const k = s.length === 3 ? 1 : 2;
      const rate = Number(s.slice(k));
      return s[k] === '0' || !rate ? null : { qty: Number(s.slice(0, k)), rate };
    },
    // The engine sometimes writes "3 60" as "360" in its first guess but offers "3 60" as an alternative: prefer that one.
    bestAlternative(result) {
      const nums = t => (t.match(/(?<![\w.])\d+(?![\w])/g) || []);
      const top = result[0].transcript, topNums = nums(top);
      for (let k = 1; k < result.length; k++) {
        const alt = result[k].transcript, altNums = nums(alt);
        if (altNums.length > topNums.length && altNums.join('') === topNums.join('') && alt.replace(/[\d\s]/g, '').toLowerCase() === top.replace(/[\d\s]/g, '').toLowerCase()) return alt;
      }
      return top;
    },
    // "hot coffee 260" (said "2 ... 60") -> "hot coffee 2 60", so the box shows what will be added. Only when it is one bare number and no quantity/rate words.
    unjoinText(t) {
      const all = t.match(/(?<![\w.])\d+(?:\.\d+)?(?![\w])/g) || [];
      if (all.length !== 1 || !/^\d{3,}$/.test(all[0])) return t;
      if (new RegExp('\b(' + QTY_WORDS + '|' + RATE_WORDS + '|' + UNIT_WORDS + ')\b', 'i').test(t)) return t;
      const p = this.bestMatch(t.replace(all[0], ' ').replace(/\s+/g, ' ').trim());
      const sp = this.splitJoined(Number(all[0]), p ? p.price : null);
      return sp ? t.replace(all[0], sp.qty + ' ' + sp.rate) : t;
    },
    // Voice -> text box (visible, editable). The person then taps "Add" to put ONE entry into the memo, and repeats for the next item.
    toggleVoice() { this.listening ? this.stopVoice() : this.startVoice(); },
    startVoice() {
      const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
      if (!SR) { this.voiceError = 'Voice input needs Chrome or Edge — type in the box instead.'; return; }
      this.voiceError = ''; this.memoMsg = ''; this.memoText = '';
      const rec = new SR();
      rec.lang = this.voiceLang; rec.continuous = false; rec.interimResults = true; rec.maxAlternatives = 5;
      rec.onresult = (e) => {
        let t = '';
        for (let i = 0; i < e.results.length; i++) t += this.bestAlternative(e.results[i]) + ' ';
        this.memoText = t.trim();            // shows in the box while speaking; stays there to check or edit
      };
      rec.onerror = (e) => {
        if (e.error === 'no-speech') { this.voiceError = 'Did not hear anything — tap the mic and speak again.'; return; }
        if (e.error === 'aborted') return;
        this.voiceError = e.error === 'not-allowed' ? 'Microphone is blocked — allow it in the browser address bar, then tap again.' : 'Voice error: ' + e.error;
      };
      rec.onend = () => {
        this.listening = false; this.interim = '';
        const fixed = this.unjoinText(this.memoText);
        if (fixed !== this.memoText) { this.memoMsg = 'Heard "' + this.memoText + '" — shown as quantity and rate. Edit the box if you meant something else.'; this.memoText = fixed; }
        const box = document.getElementById('memoSayBox'); if (box && this.memoText) box.focus(); };
      this.rec = rec;
      try { rec.start(); this.listening = true; } catch (err) { this.voiceError = 'Could not start the microphone.'; }
    },
    stopVoice() { this.listening = false; if (this.rec) { try { this.rec.stop(); } catch (e) {} } },
    // Submit: every filled row goes to the cart (catalogue items at the memo rate, the rest as free items).
    memoSubmit() {
      if (this.listening) this.stopVoice();
      const used = this.memoRows.filter(r => r.item.trim() !== '' || r.qty !== '' || r.rate !== '');
      if (!used.length) { this.memoMsg = 'Say or write at least one item first.'; return; }
      let bad = 0;
      this.memoErr = '';
      for (const r of used) {
        r.error = '';
        if (!/\p{L}/u.test(r.item)) { r.error = 'Item name missing'; bad++; continue; }
        if (!r.pid) this.matchRow(r);
        if (r.rate === '' || r.rate === null || !(Number(r.rate) >= 0)) { r.error = 'Enter a rate'; bad++; }
      }
      if (bad) {
        this.memoMsg = '';
        this.memoErr = bad + ' row' + (bad === 1 ? ' needs' : 's need') + ' fixing (shown in red) — add the missing rate/name, or tap ✕ to remove the row.';
        this.$nextTick(() => { const el = document.querySelector('[data-memo-err="1"]'); if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' }); });
        return;
      }
      for (const r of used) {
        const qty = this.baseQty({ ...r, qty: Number(r.qty) > 0 ? Number(r.qty) : 1 });
        const rate = Number(r.rate);
        const p = r.pid ? this.memoItems().find(x => x.id === r.pid) : null;
        const base = this.unitInfo(r).base;
        if (p) this.memoPushCatalog(p, qty, rate);
        else this.memoPushFree(r.item.trim() + (base !== 'pcs' ? ' (' + base + ')' : ''), qty, rate, Number(r.gst) || 0);
      }
      this.memoErr = '';
      this.memoMsg = used.length + ' item' + (used.length === 1 ? '' : 's') + ' added to the cart.';
      this.memoRows = []; this.memoAddRows(5); this.heard = [];
      this.memoDone();
    },
  };
}
</script>
