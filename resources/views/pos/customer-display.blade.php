<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><title>شاشة العميل</title>
    <style>
        body{margin:0;background:#07111f;color:white;font-family:Tahoma,sans-serif;min-height:100vh;display:grid;grid-template-rows:auto 1fr auto}.head{padding:24px;background:#0f1f33;font-size:28px;font-weight:bold}.items{padding:24px;overflow:auto}.item{display:flex;justify-content:space-between;padding:15px 0;border-bottom:1px solid #334155;font-size:24px}.total{display:flex;justify-content:space-between;background:#f59e0b;color:#111827;padding:24px;font-size:38px;font-weight:900}.empty{display:grid;place-items:center;height:100%;font-size:32px;color:#94a3b8}
    </style>
</head>
<body>
<header class="head">{{ config('app.name') }} — أهلاً بكم</header>
<main class="items" id="items"><div class="empty">بانتظار إضافة المنتجات</div></main>
<footer class="total"><span>الإجمالي</span><span id="total">0.00 ج.م</span></footer>
<script>
const channel = new BroadcastChannel('daftar-pos-display');
channel.onmessage = ({data}) => {
    const cart = data.cart || [];
    document.getElementById('items').innerHTML = cart.length ? cart.map(item =>
        `<div class="item"><span>${item.name} × ${item.quantity}</span><strong>${((item.quantity*item.unit_price)-item.discount_amount).toFixed(2)}</strong></div>`
    ).join('') : '<div class="empty">بانتظار إضافة المنتجات</div>';
    document.getElementById('total').textContent = Number(data.totals?.grand_total || 0).toFixed(2) + ' ج.م';
};
</script>
</body>
</html>
