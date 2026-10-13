<?php
/**
 * Send a finished bill on WhatsApp as a picture (PNG) or a PDF.
 * The including view first requires bill-share-data.php, builds $billShare with billShareFromSale() / billShareFromInvoice(),
 * then requires this file just before the page footer. Buttons call tpxShareBill('image' | 'pdf').
 * The picture is drawn in the browser (canvas); the PDF wraps that picture on one page - no server work, no library.
 */
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
window.TPX_BILL = <?= json_encode($billShare, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

(function () {
  var busy = false;
  function num(n) { return Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function qty(q) { return String(Math.round(Number(q) * 100) / 100); }

  function toast(msg) {
    var t = document.getElementById('tpxShareToast');
    if (!t) {
      t = document.createElement('div'); t.id = 'tpxShareToast';
      t.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);max-width:92vw;background:#0f172a;color:#fff;padding:10px 16px;border-radius:10px;font:500 13px/1.4 system-ui,sans-serif;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,.3);text-align:center';
      document.body.appendChild(t);
    }
    t.textContent = msg; t.style.display = msg ? 'block' : 'none';
    clearTimeout(t._h); if (msg) t._h = setTimeout(function () { t.style.display = 'none'; }, 7000);
  }

  // ── draw the bill ──
  function draw(b) {
    var S = 2, W = 400, PAD = 18, R = W - PAD;
    var tall = document.createElement('canvas'); tall.width = W * S; tall.height = 6000 * S;
    var c = tall.getContext('2d'); c.scale(S, S); c.fillStyle = '#ffffff'; c.fillRect(0, 0, W, 6000);
    var FONT = '"Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Noto Sans Bengali", Arial, sans-serif', y = 24;
    function f(size, weight) { c.font = (weight || '400') + ' ' + size + 'px ' + FONT; }
    function wrap(text, maxW, size, weight) {
      f(size, weight); var words = String(text).split(/\s+/), lines = [], cur = '';
      words.forEach(function (w) { var t = cur ? cur + ' ' + w : w; if (c.measureText(t).width <= maxW || !cur) cur = t; else { lines.push(cur); cur = w; } });
      if (cur) lines.push(cur); return lines;
    }
    function ctext(t, size, weight, color) { f(size, weight); c.fillStyle = color || '#0f172a'; c.textAlign = 'center'; c.fillText(t, W / 2, y); }
    function ltext(t, x, size, weight, color) { f(size, weight); c.fillStyle = color || '#0f172a'; c.textAlign = 'left'; c.fillText(t, x, y); }
    function rtext(t, x, size, weight, color) { f(size, weight); c.fillStyle = color || '#0f172a'; c.textAlign = 'right'; c.fillText(t, x, y); }
    function rule(x1, x2, color, w, dash) { c.save(); c.strokeStyle = color || '#cbd5e1'; c.lineWidth = w || 1; c.setLineDash(dash || []); c.beginPath(); c.moveTo(x1, y); c.lineTo(x2, y); c.stroke(); c.restore(); }

    // letterhead
    wrap(b.shop.name, W - 2 * PAD, 19, '700').forEach(function (t) { ctext(t, 19, '700'); y += 24; });
    if (b.shop.address) wrap(b.shop.address, W - 2 * PAD, 11).forEach(function (t) { ctext(t, 11, '400', '#475569'); y += 15; });
    var contact = (b.shop.phone ? 'Ph: ' + b.shop.phone : '') + (b.shop.gstin ? (b.shop.phone ? '    ' : '') + 'GSTIN: ' + b.shop.gstin : '');
    if (contact) { ctext(contact, 11, '400', '#475569'); y += 18; }
    y += 6;
    f(11, '700'); var tw = c.measureText(b.title.toUpperCase()).width + 30;
    c.fillStyle = '#0f172a'; c.fillRect((W - tw) / 2, y - 13, tw, 23);
    ctext(b.title.toUpperCase(), 11, '700', '#ffffff'); y += 30;
    if (b.cancelled) { ctext('CANCELLED', 14, '700', '#dc2626'); y += 22; }

    // bill-to / number
    ltext('Billed to', PAD, 10, '400', '#64748b'); rtext('Invoice no.', R, 10, '400', '#64748b'); y += 15;
    ltext(b.customer, PAD, 12, '600'); rtext(b.no, R, 12, '600'); y += 15;
    var sub = (b.mobile ? b.mobile : '') + (b.custGstin ? (b.mobile ? '  ' : '') + 'GSTIN ' + b.custGstin : '');
    if (sub) ltext(sub, PAD, 10, '400', '#64748b');
    rtext(b.date, R, 10, '400', '#64748b'); y += 16;

    // items
    var X = { n: PAD, item: PAD + 18, qty: 262, rate: 322, amt: R };
    y += 4; rule(PAD, R, '#0f172a', 1); y += 15;
    ltext('#', X.n, 10, '700'); ltext('Item', X.item, 10, '700'); rtext('Qty', X.qty, 10, '700'); rtext('Rate', X.rate, 10, '700'); rtext('Amount', X.amt, 10, '700');
    y += 7; rule(PAD, R, '#0f172a', 1); y += 17;
    b.lines.forEach(function (l, i) {
      var nl = wrap(l.name, X.qty - X.item - 36, 12, '500');
      ltext(String(i + 1), X.n, 12, '400', '#64748b');
      nl.forEach(function (t, k) { ltext(t, X.item, 12, '500'); if (k < nl.length - 1) y += 15; });
      rtext(qty(l.qty), X.qty, 12, '400'); rtext(num(l.rate), X.rate, 12, '400'); rtext(num(l.amount), X.amt, 12, '600');
      var meta = [];
      if (l.hsn) meta.push('HSN ' + l.hsn);
      if (b.showGst && l.gst > 0) meta.push('GST ' + l.gst + '%');
      if (meta.length) { y += 13; ltext(meta.join('  ·  '), X.item, 10, '400', '#64748b'); }
      y += 8; rule(PAD, R, '#e2e8f0', 1); y += 16;
    });

    // totals
    var LX = 200;
    b.totals.forEach(function (t) {
      var neg = t.value < 0;
      ltext(t.label, LX, 12, '400', '#475569'); rtext((neg ? '- ' : '') + num(Math.abs(t.value)), R, 12, '600', '#334155'); y += 19;
    });
    y += 1; rule(LX, R, '#0f172a', 1.3); y += 20;
    ltext('Grand Total', LX, 15, '700'); rtext('₹' + num(b.grand), R, 15, '700'); y += 24;
    wrap(b.words, W - 2 * PAD, 10).forEach(function (t) { ltext(t, PAD, 10, '400', '#64748b'); y += 14; });
    y += 6;

    // payment
    c.fillStyle = '#f1f5f9'; c.fillRect(PAD, y - 13, W - 2 * PAD, 26);
    ltext(b.paidLabel, PAD + 8, 12, '400', '#475569'); rtext('₹' + num(b.paid), R - 8, 12, '700'); y += 28;
    if (b.balance > 0.004) {
      c.fillStyle = '#fffbeb'; c.fillRect(PAD, y - 13, W - 2 * PAD, 26);
      ltext(b.balanceLabel, PAD + 8, 12, '700', '#b45309'); rtext('₹' + num(b.balance), R - 8, 12, '700', '#b45309'); y += 32;
      // UPI QR so the customer can clear the balance straight away
      if (b.shop.upi && typeof qrcode === 'function' && !b.cancelled) {
        try {
          var link = 'upi://pay?pa=' + encodeURIComponent(b.shop.upi) + '&pn=' + encodeURIComponent(b.shop.name) + '&am=' + Number(b.balance).toFixed(2) + '&cu=INR&tn=' + encodeURIComponent('Bill ' + b.no);
          var qr = qrcode(0, 'M'); qr.addData(link); qr.make();
          var n = qr.getModuleCount(), size = 124, cell = size / n, qx = (W - size) / 2;
          c.fillStyle = '#ffffff'; c.fillRect(qx - 6, y - 6, size + 12, size + 12);
          c.fillStyle = '#0f172a';
          for (var r = 0; r < n; r++) for (var k = 0; k < n; k++) if (qr.isDark(r, k)) c.fillRect(qx + k * cell, y + r * cell, Math.ceil(cell), Math.ceil(cell));
          y += size + 20; ctext('Scan to pay ₹' + num(b.balance), 11, '600', '#334155'); y += 15; ctext(b.shop.upi, 10, '400', '#64748b'); y += 20;
        } catch (e) { /* the QR is optional */ }
      }
    }
    y += 6; rule(PAD, R, '#94a3b8', 1, [4, 3]); y += 18;
    if (b.shop.footer) { ctext(b.shop.footer, 11, '400', '#475569'); y += 18; }
    y += 8;

    var out = document.createElement('canvas'); out.width = W * S; out.height = Math.ceil(y * S);
    var oc = out.getContext('2d'); oc.fillStyle = '#ffffff'; oc.fillRect(0, 0, out.width, out.height);
    oc.drawImage(tall, 0, 0, W * S, out.height, 0, 0, W * S, out.height);
    return out;
  }

  // one-page PDF that holds the picture
  function pdf(canvas) {
    var jpeg = atob(canvas.toDataURL('image/jpeg', 0.92).split(',')[1]), img = new Uint8Array(jpeg.length);
    for (var i = 0; i < jpeg.length; i++) img[i] = jpeg.charCodeAt(i);
    var pw = 420, ph = Math.round(pw * canvas.height / canvas.width), parts = [], off = 0, xr = [];
    function enc(s) { return new TextEncoder().encode(s); }
    function push(u) { parts.push(u); off += u.length; }
    function obj(n, head, stream) { xr[n] = off; push(enc(n + ' 0 obj\n' + head + '\n')); if (stream) { push(enc('stream\n')); push(stream); push(enc('\nendstream\n')); } push(enc('endobj\n')); }
    push(enc('%PDF-1.4\n'));
    obj(1, '<< /Type /Catalog /Pages 2 0 R >>');
    obj(2, '<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
    obj(3, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + pw + ' ' + ph + '] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>');
    obj(4, '<< /Type /XObject /Subtype /Image /Width ' + canvas.width + ' /Height ' + canvas.height + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + img.length + ' >>', img);
    var content = enc('q ' + pw + ' 0 0 ' + ph + ' 0 0 cm /Im0 Do Q');
    obj(5, '<< /Length ' + content.length + ' >>', content);
    var pos = off, x = 'xref\n0 6\n0000000000 65535 f \n';
    for (var n = 1; n <= 5; n++) x += String(xr[n]).padStart(10, '0') + ' 00000 n \n';
    push(enc(x + 'trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n' + pos + '\n%%EOF\n'));
    return new Blob(parts, { type: 'application/pdf' });
  }

  window.tpxShareBill = function (kind) {
    var b = window.TPX_BILL; if (!b || busy) return;
    busy = true; toast('Preparing the bill…');
    var finish = function (msg) { busy = false; toast(msg || ''); };
    try {
      var canvas = draw(b), name = b.no.replace(/[^A-Za-z0-9_-]/g, '_') + (kind === 'pdf' ? '.pdf' : '.png');
      var text = b.shop.name + ' — ' + b.title + ' ' + b.no + ': ₹' + num(b.grand);
      var make = function (cb) { if (kind === 'pdf') cb(pdf(canvas)); else canvas.toBlob(cb, 'image/png'); };
      make(function (blob) {
        var file = new File([blob], name, { type: blob.type });
        var save = function () {
          var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name; document.body.appendChild(a); a.click(); a.remove();
          setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
        };
        // phone: system share sheet → WhatsApp → customer
        if (navigator.canShare && navigator.canShare({ files: [file] })) {
          navigator.share({ files: [file], title: b.title + ' ' + b.no, text: text })
            .then(function () { finish('Shared.'); })
            .catch(function (e) { if (e && e.name === 'AbortError') finish(''); else { save(); finish('Saved ' + name + ' — attach it in WhatsApp.'); } });
          return;
        }
        // computer: save the file, copy the picture, open the customer's chat
        save();
        var m = String(b.mobile || '').replace(/\D/g, '');
        if (!m) { var typed = prompt("Customer's WhatsApp number (10 digits). Leave empty to choose the contact inside WhatsApp."); m = typed ? typed.replace(/\D/g, '') : ''; }
        if (m.length === 10) m = '91' + m;
        var open = function (note) { window.open('https://wa.me/' + m + '?text=' + encodeURIComponent(text), '_blank', 'noopener'); finish(note); };
        if (kind !== 'pdf' && navigator.clipboard && window.ClipboardItem) {
          navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })])
            .then(function () { open('Bill image saved and copied — in the WhatsApp chat press Ctrl+V to paste it, then send.'); })
            .catch(function () { open('Bill saved as ' + name + ' — in the WhatsApp chat use the attach button and pick it.'); });
        } else {
          open('Bill saved as ' + name + ' — in the WhatsApp chat use the attach button and pick it.');
        }
      });
    } catch (e) { finish('Could not create the bill file: ' + (e && e.message ? e.message : e)); }
  };
})();
</script>
