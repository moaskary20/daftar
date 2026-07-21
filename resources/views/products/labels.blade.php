<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طباعة باركود {{ $product->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #172033;
            --muted: #6b7280;
            --primary: #f59e0b;
            --surface: #ffffff;
            --background: #f4f6fa;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(circle at 20% 10%, rgba(245, 158, 11, .12), transparent 26rem),
                var(--background);
            font-family: 'Tajawal', sans-serif;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: white;
            background: rgba(23, 32, 51, .94);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .18);
        }

        .toolbar h1 { margin: 0; font-size: 1.1rem; }
        .toolbar-actions { display: flex; align-items: center; gap: .75rem; }

        .toolbar input {
            width: 5rem;
            padding: .65rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: .7rem;
            color: white;
            background: rgba(255, 255, 255, .1);
        }

        .button {
            padding: .7rem 1.1rem;
            border: 0;
            border-radius: .7rem;
            color: #1f2937;
            background: var(--primary);
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(245, 158, 11, .35);
        }

        .labels {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 1rem;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .label {
            direction: rtl;
            display: grid;
            grid-template-columns: 1fr 78px;
            gap: .7rem;
            min-height: 150px;
            padding: .9rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: var(--surface);
            box-shadow: 0 12px 35px rgba(15, 23, 42, .08);
            animation: label-in .45s ease both;
        }

        .label-main { min-width: 0; text-align: center; }
        .product-name { margin: 0 0 .2rem; font-size: 1rem; font-weight: 800; }
        .variant-name { min-height: 1rem; margin: 0 0 .3rem; color: var(--muted); font-size: .8rem; }
        .barcode svg { max-width: 100%; height: 72px; }
        .meta { display: flex; justify-content: space-between; gap: .5rem; margin-top: .2rem; font-size: .74rem; }
        .price { color: #b45309; font-size: .95rem; font-weight: 800; }
        .qr { display: flex; align-items: center; justify-content: center; border-right: 1px dashed #d1d5db; padding-right: .7rem; }
        .qr svg { width: 68px; height: 68px; }

        @keyframes label-in {
            from { opacity: 0; transform: translateY(12px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media print {
            @page { size: auto; margin: 5mm; }
            body { background: white; }
            .toolbar { display: none; }
            .labels {
                grid-template-columns: repeat(3, 1fr);
                gap: 3mm;
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .label {
                min-height: 36mm;
                padding: 3mm;
                border-radius: 2mm;
                box-shadow: none;
                break-inside: avoid;
                animation: none;
            }
        }
    </style>
</head>
<body>
    <header class="toolbar">
        <h1>ملصقات: {{ $product->name }}{{ $selectedVariant ? ' — ' . $selectedVariant->name : '' }}</h1>
        <form class="toolbar-actions" method="get">
            @if ($selectedVariant)
                <input type="hidden" name="variant" value="{{ $selectedVariant->id }}">
            @endif
            <label for="copies">عدد النسخ</label>
            <input id="copies" type="number" name="copies" value="{{ $copies }}" min="1" max="100">
            <button class="button" type="submit">تحديث</button>
            <button class="button" type="button" onclick="window.print()">طباعة</button>
        </form>
    </header>

    <main class="labels">
        @for ($copy = 0; $copy < $copies; $copy++)
            @foreach ($labels as $label)
                <article class="label" style="animation-delay: {{ min((($copy * $labels->count()) + $loop->index) * 35, 350) }}ms">
                    <div class="label-main">
                        <h2 class="product-name">{{ $product->name }}</h2>
                        <p class="variant-name">{{ $label['variantName'] }}</p>
                        <div class="barcode">{!! $label['barcodeSvg'] !!}</div>
                        <div class="meta">
                            <span>{{ $label['item']->sku }}</span>
                            <strong class="price">{{ number_format((float) ($label['item']->selling_price ?? $product->selling_price), 2) }} ج.م</strong>
                        </div>
                    </div>
                    <div class="qr">{!! $label['qrSvg'] !!}</div>
                </article>
            @endforeach
        @endfor
    </main>
</body>
</html>
