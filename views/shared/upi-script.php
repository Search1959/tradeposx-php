<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
/** UPI pay-by-QR for the checkout screens. The host spreads this in (`...upiMixin(upiId, payeeName)`), gives its form
 * x-ref="checkoutForm" and a `paymentMethod` field, and in its onSubmit does:
 *   if (this.upiNeeded(amountPaidNow)) { e.preventDefault(); this.upiShow(amountPaidNow); return; } */
function upiMixin(upiId, payee) {
  return {
    upiId: upiId || '', upiPayee: payee || '', upiOpen: false, upiPaid: false, upiAmount: 0, upiLink: '',
    upiNeeded(amount) { return this.paymentMethod === 'UPI' && !!this.upiId && !this.upiPaid && Number(amount) > 0; },
    upiShow(amount) {
      this.upiAmount = Math.round(Number(amount) * 100) / 100;
      this.upiLink = 'upi://pay?pa=' + encodeURIComponent(this.upiId) + '&pn=' + encodeURIComponent(this.upiPayee)
        + '&am=' + this.upiAmount.toFixed(2) + '&cu=INR&tn=' + encodeURIComponent('Bill payment');
      this.upiOpen = true;
      this.$nextTick(() => {
        const box = this.$refs.upiQr; if (!box) return;
        try { const qr = qrcode(0, 'M'); qr.addData(this.upiLink); qr.make(); box.innerHTML = qr.createSvgTag({ scalable: true, margin: 0 }); const svg = box.querySelector('svg'); if (svg) { svg.setAttribute('width', '100%'); svg.setAttribute('height', '100%'); } }
        catch (e) { box.textContent = 'Could not draw the QR code — pay to the UPI ID shown below.'; }
      });
    },
    upiConfirm() {
      this.upiPaid = true; this.upiOpen = false;
      this.$nextTick(() => { const f = this.$refs.checkoutForm; if (f.requestSubmit) f.requestSubmit(); else f.submit(); });
    },
  };
}
</script>