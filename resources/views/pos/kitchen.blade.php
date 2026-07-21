<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طلب مطبخ {{ $document->number }}</title>
    <style>
        @page{size:80mm auto;margin:2mm}body{width:76mm;margin:0;font-family:Tahoma,sans-serif;font-size:14px}h1,p{text-align:center}table{width:100%;border-collapse:collapse}th,td{padding:8px 2px;border-bottom:1px dashed #111}td:last-child{font-size:20px;font-weight:bold;text-align:left}.notes{border:2px solid #111;padding:8px;margin-top:10px}@media print{button{display:none}}
    </style>
</head>
<body>
<button onclick="window.print()">طباعة</button>
<h1>طلب مطبخ</h1>
<p>{{ $document->number }}<br>{{ $document->posted_at?->format('H:i') }}</p>
<table>
    <thead><tr><th>الطلب</th><th>الكمية</th></tr></thead>
    <tbody>
    @forelse($document->items as $item)
        <tr><td>{{ $item->product->name }}<br><small>{{ $item->description }}</small></td><td>{{ number_format((float)$item->quantity, 0) }}</td></tr>
    @empty
        <tr><td colspan="2">لا توجد عناصر موجهة للمطبخ.</td></tr>
    @endforelse
    </tbody>
</table>
@if($document->notes)<div class="notes">{{ $document->notes }}</div>@endif
<script>window.addEventListener('load',()=>{if(document.querySelectorAll('tbody tr').length) setTimeout(()=>window.print(),250)});</script>
</body>
</html>
