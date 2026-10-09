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

  return {
    leftTab: 'stock',
    memoRows: [], memoSeq: 0, memoText: '', memoMsg: '', voiceError: '', voiceLang: 'en-IN',
    listening: false, wantListening: false, rec: null, interim: '', heard: [], typeOpen: false,
    voiceSupported: !!(window.SpeechRecognition || window.webkitSpeechRecognition),

    memoInit() { this.memoAddRows(5); if (!this.voiceSupported) this.typeOpen = true; },
    blankRow() { return { k: ++this.memoSeq, item: '', qty: '', rate: '', pid: null, gst: 18, stock: null, error: '' }; },
    memoAddRows(n) { for (let i = 0; i < n; i++) this.memoRows.push(this.blankRow()); },
    memoRemove(i) { this.memoRows.splice(i, 1); if (this.memoRows.length === 0) this.memoAddRows(1); },
    memoClear() { this.memoRows = []; this.memoAddRows(5); this.memoMsg = ''; this.heard = []; },
    memoAmount(r) { return (Number(r.qty) || (r.item.trim() ? 1 : 0)) * (Number(r.rate) || 0); },
    memoTotal() { return this.memoRows.reduce((s, r) => s + this.memoAmount(r), 0); },
    memoEnter(i) {
      if (i === this.memoRows.length - 1) this.memoAddRows(1);
      this.$nextTick(() => { const boxes = document.querySelectorAll('[data-memo-item]'); if (boxes[i + 1]) boxes[i + 1].focus(); });
    },
    // Exact name (from the suggestions) -> linked to the catalogue item with its rate and GST; anything else stays a free item.
    matchRow(r) {
      const name = r.item.trim().toLowerCase();
      const p = name ? this.memoItems().find(x => x.name.toLowerCase() === name) : null;
      if (p) { r.pid = p.id; r.gst = p.gst; r.stock = p.stock; if (r.rate === '' || r.rate === null) r.rate = p.price; }
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
      const filler = /\b(quantity|qty|pieces|piece|pcs|nos|number|at|rate|price|rs|rupees|rupee|each|of|x|for)\b|@|₹/gi;
      const text = wordsToDigits(raw);
      const segs = text.replace(/\b(next|then|aur|and next)\b/gi, '\n').split(/[\n;,]+/).map(t => t.trim()).filter(Boolean);
      let added = 0;
      for (const seg of segs) {
        const NUM = /(?<![\w.])\d+(?:\.\d+)?(?![\w])/g; // standalone numbers only, so "10000mAh" stays part of the name
        const nums = [...seg.matchAll(NUM)].map(m => Number(m[0]));
        const name = seg.replace(NUM, ' ').replace(filler, ' ').replace(/\s+/g, ' ').trim();
        if (!name) continue;
        const row = this.blankRow();
        const p = this.bestMatch(name);
        row.item = p ? p.name : name.replace(/\b\w/g, c => c.toUpperCase());
        if (p) { row.pid = p.id; row.gst = p.gst; row.stock = p.stock; }
        row.qty = nums.length ? nums[0] : 1;
        row.rate = nums.length > 1 ? nums[1] : (p ? p.price : '');
        const blank = this.memoRows.findIndex(r => r.item.trim() === '' && r.qty === '' && r.rate === '');
        if (blank >= 0) this.memoRows.splice(blank, 1, row); else this.memoRows.push(row);
        added++;
      }
      if (added) this.heard.push(raw.trim());
      return added;
    },
    parseMemoText() {
      const n = this.parseChunk(this.memoText);
      this.memoText = '';
      this.memoMsg = n ? n + ' row' + (n === 1 ? '' : 's') + ' filled — check them, then Add to cart.' : 'Nothing understood — say or type "item, quantity, rate".';
    },
    toggleVoice() { this.listening ? this.stopVoice() : this.startVoice(); },
    startVoice() {
      const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
      if (!SR) { this.voiceError = 'Voice input needs Chrome or Edge.'; this.typeOpen = true; return; }
      this.voiceError = ''; this.memoMsg = ''; this.wantListening = true;
      let failed = false;
      const rec = new SR();
      rec.lang = this.voiceLang; rec.continuous = true; rec.interimResults = true;
      rec.onresult = (e) => {
        let interim = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
          const res = e.results[i];
          if (res.isFinal) {
            const n = this.parseChunk(res[0].transcript);
            this.memoMsg = n ? 'Heard: "' + res[0].transcript.trim() + '"' : '';
          } else { interim += res[0].transcript; }
        }
        this.interim = interim;
      };
      rec.onerror = (e) => {
        if (e.error === 'no-speech' || e.error === 'aborted') return;
        failed = true; this.wantListening = false;
        this.voiceError = e.error === 'not-allowed' ? 'Microphone is blocked — allow it in the browser address bar, then tap again.' : 'Voice error: ' + e.error;
      };
      // Chrome ends continuous recognition after a pause — keep listening until the user taps Stop.
      rec.onend = () => {
        this.interim = '';
        if (this.wantListening && !failed) { try { rec.start(); return; } catch (err) { /* fall through */ } }
        this.listening = false;
      };
      this.rec = rec;
      try { rec.start(); this.listening = true; } catch (err) { this.voiceError = 'Could not start the microphone.'; this.wantListening = false; }
    },
    stopVoice() { this.wantListening = false; this.listening = false; if (this.rec) { try { this.rec.stop(); } catch (e) {} } this.interim = ''; },
    // Submit: every filled row goes to the cart (catalogue items at the memo rate, the rest as free items).
    memoSubmit() {
      if (this.listening) this.stopVoice();
      const used = this.memoRows.filter(r => r.item.trim() !== '' || r.qty !== '' || r.rate !== '');
      if (!used.length) { this.memoMsg = 'Say or write at least one item first.'; return; }
      let bad = false;
      for (const r of used) {
        r.error = '';
        if (r.item.trim() === '') { r.error = 'Item name missing'; bad = true; continue; }
        if (!r.pid) this.matchRow(r);
        if (r.rate === '' || r.rate === null || !(Number(r.rate) >= 0)) { r.error = 'Enter a rate'; bad = true; }
      }
      if (bad) { this.memoMsg = ''; return; }
      for (const r of used) {
        const qty = Number(r.qty) > 0 ? Number(r.qty) : 1;
        const rate = Number(r.rate);
        const p = r.pid ? this.memoItems().find(x => x.id === r.pid) : null;
        if (p) this.memoPushCatalog(p, qty, rate); else this.memoPushFree(r.item.trim(), qty, rate, Number(r.gst) || 0);
      }
      this.memoMsg = used.length + ' item' + (used.length === 1 ? '' : 's') + ' added to the cart.';
      this.memoRows = []; this.memoAddRows(5); this.heard = [];
      this.memoDone();
    },
  };
}
</script>
