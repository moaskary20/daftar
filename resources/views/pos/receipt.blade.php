<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $document->number }}</title>
    <style>
        @page { margin: {{ $size === 'a4' ? '12mm' : '2mm' }}; size: {{ $size === 'a4' ? 'A4' : $size.' auto' }}; }
        * { box-sizing:border-box }
        body { margin:0 auto; color:#111; background:white; font-family:"Tajawal",Tahoma,sans-serif; font-size:{{ $size === '58mm' ? '10px' : '12px' }}; width:{{ $size === 'a4' ? '190mm' : $size }}; }
        .receipt { padding:{{ $size === 'a4' ? '8mm' : '2mm' }} }
        .head { text-align:center;border-bottom:1px dashed #111;padding-bottom:8px }.head h1{margin:0;font-size:1.6em}.head p{margin:2px}
        .meta { display:grid;grid-template-columns:1fr 1fr;gap:3px;padding:8px 0;border-bottom:1px dashed #111 }
        table{width:100%;border-collapse:collapse}th,td{padding:5px 2px;text-align:right;border-bottom:1px dotted #aaa}th:last-child,td:last-child{text-align:left}
        .totals{margin-top:8px;margin-inline-start:auto;max-width:{{ $size === 'a4' ? '85mm' : '100%' }}}.totals div{display:flex;justify-content:space-between;padding:3px}.grand{font-size:1.25em;font-weight:bold;border-top:2px solid #111}
        .payments{border-top:1px dashed #111;margin-top:8px;padding-top:5px}.footer{text-align:center;border-top:1px dashed #111;margin-top:10px;padding-top:8px}
        .actions{position:fixed;top:10px;left:10px;display:flex;gap:5px}.actions button{padding:8px;border:0;border-radius:5px;background:#f59e0b;font-weight:bold}
        @media print {.actions{display:none}}
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()">طباعة</button><button onclick="window.close()">إغلاق</button></div>
<main class="receipt">
    <header class="head">
        <h1>{{ config('app.name') }}</h1>
        <p>فاتورة مبيعات</p>
        <strong>{{ $document->number }}</strong>
    </header>
    <section class="meta">
        <span>التاريخ: {{ $document->posted_at?->format('Y-m-d H:i') }}</span>
        <span>الكاشير: {{ $document->creator?->name }}</span>
        <span>العميل: {{ $document->customer?->name }}</span>
        <span>طريقة البيع: {{ ['cash'=>'نقدي','credit'=>'آجل','installment'=>'تقسيط','mixed'=>'متعدد'][$document->payment_type] ?? $document->payment_type }}</span>
    </section>
    <table>
        <thead><tr><th>المنتج</th><th>ك</th><th>السعر</th><th>الإجمالي</th></tr></thead>
        <tbody>
        @foreach($document->items as $item)
            <tr>
                <td>{{ $item->product->name }}{{ $item->variant ? ' - '.$item->variant->name : '' }} @if($item->serial_number)<small><br>SN: {{ $item->serial_number }}</small>@endif</td>
                <td>{{ number_format((float)$item->quantity, 2) }}</td>
                <td>{{ number_format((float)$item->unit_price, 2) }}</td>
                <td>{{ number_format((float)$item->line_total + (float)$item->tax_amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <section class="totals">
        <div><span>الصافي</span><span>{{ number_format((float)$document->subtotal, 2) }}</span></div>
        <div><span>الخصم</span><span>{{ number_format((float)$document->discount_total, 2) }}</span></div>
        <div><span>الضريبة</span><span>{{ number_format((float)$document->tax_total, 2) }}</span></div>
        <div class="grand"><span>الإجمالي</span><span>{{ number_format((float)$document->grand_total, 2) }} ج.م</span></div>
    </section>
    @if($document->payments->isNotEmpty())
        <section class="payments">
            @foreach($document->payments as $payment)
                <div>{{ \App\Models\PosPayment::methodLabels()[$payment->method] ?? $payment->method }}: {{ number_format((float)$payment->amount, 2) }} ج.م</div>
            @endforeach
        </section>
    @endif
    <footer class="footer">
        <p>شكراً لتعاملكم معنا</p>
        <small>عدد مرات الطباعة: {{ $document->print_count }}</small>
    </footer>
</main>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),250));</script>
</body>
</html>
