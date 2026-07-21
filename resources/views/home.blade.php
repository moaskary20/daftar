<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دفتر — تسجيل الدخول</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.25rem;
            background: linear-gradient(160deg, #37309f 0%, #4238ca 45%, #5458f0 100%);
            position: relative;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: .5;
            pointer-events: none;
            animation: float 14s ease-in-out infinite;
        }

        .blob-1 { width: 420px; height: 420px; background: #f59e0b; top: -140px; left: -110px; }
        .blob-2 { width: 360px; height: 360px; background: #22d3ee; bottom: -120px; right: -100px; animation-delay: -5s; }
        .blob-3 { width: 260px; height: 260px; background: #f472b6; bottom: 18%; left: 12%; opacity: .32; animation-delay: -9s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-34px) scale(1.06); }
        }

        .card {
            position: relative;
            width: min(430px, 100%);
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(18px);
            border-radius: 1.5rem;
            padding: 2.4rem 2.1rem 1.7rem;
            box-shadow: 0 40px 90px rgba(9, 9, 45, .45);
            animation: reveal .55s ease both;
        }

        @keyframes reveal {
            from { opacity: 0; transform: translateY(26px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 1.6rem;
        }

        .brand-badge {
            width: 74px;
            height: 74px;
            margin: 0 auto .8rem;
            border-radius: 1.25rem;
            display: grid;
            place-items: center;
            background: linear-gradient(140deg, #5458f0, #37309f);
            color: #fff;
            font-size: 2rem;
            font-weight: 900;
            box-shadow: 0 16px 34px rgba(66, 56, 202, .45);
            rotate: -6deg;
        }

        .brand h1 { font-size: 1.7rem; font-weight: 900; color: #1e1b4b; }
        .brand p { color: #64748b; margin-top: .25rem; font-size: .92rem; }

        .field { margin-bottom: 1rem; }

        .field label {
            display: block;
            font-weight: 800;
            font-size: .85rem;
            color: #334155;
            margin-bottom: .4rem;
        }

        .control {
            display: flex;
            align-items: center;
            gap: .6rem;
            border: 1.5px solid #e2e8f0;
            border-radius: .9rem;
            padding: 0 .9rem;
            background: #f8fafc;
            transition: border-color .18s, box-shadow .18s, background .18s;
        }

        .control:focus-within {
            border-color: #5458f0;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(84, 88, 240, .16);
        }

        .control svg { width: 1.2rem; color: #94a3b8; flex: none; }

        .control input {
            flex: 1;
            border: 0;
            outline: 0;
            background: transparent;
            min-height: 50px;
            font-family: inherit;
            font-size: 1rem;
            color: #0f172a;
        }

        .toggle-pass {
            border: 0;
            background: transparent;
            cursor: pointer;
            display: grid;
            place-items: center;
            color: #94a3b8;
            padding: .3rem;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .85rem;
            color: #475569;
            font-weight: 700;
            cursor: pointer;
        }

        .remember input {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: #5458f0;
            cursor: pointer;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: .8rem;
            padding: .65rem .9rem;
            font-size: .85rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .submit {
            width: 100%;
            border: 0;
            cursor: pointer;
            min-height: 54px;
            border-radius: .95rem;
            font-family: inherit;
            font-size: 1.08rem;
            font-weight: 900;
            color: #fff;
            background: linear-gradient(120deg, #5458f0, #4238ca);
            box-shadow: 0 16px 32px rgba(66, 56, 202, .4);
            transition: transform .16s, box-shadow .16s;
        }

        .submit:hover { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(66, 56, 202, .5); }
        .submit:active { transform: translateY(0); }

        .creds {
            margin-top: 1.5rem;
            border: 1.5px dashed #c7d2fe;
            background: #eef2ff;
            border-radius: 1rem;
            padding: .9rem 1rem;
        }

        .creds h2 {
            font-size: .8rem;
            color: #4338ca;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .55rem;
        }

        .creds h2 svg { width: 1rem; }

        .cred-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .3rem 0;
            font-size: .88rem;
        }

        .cred-line span { color: #475569; font-weight: 700; }

        .cred-value {
            direction: ltr;
            font-weight: 800;
            color: #1e1b4b;
            background: #fff;
            border: 1px solid #e0e7ff;
            border-radius: .55rem;
            padding: .25rem .6rem;
            cursor: pointer;
            transition: background .15s;
        }

        .cred-value:hover { background: #f5f3ff; }

        .footer {
            text-align: center;
            margin-top: 1.2rem;
            color: #94a3b8;
            font-size: .78rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <main class="card">
        <div class="brand">
            <div class="brand-badge">د</div>
            <h1>دفتر</h1>
            <p>نظام إدارة المبيعات والمخازن والحسابات</p>
        </div>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('home.login') }}">
            @csrf

            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <div class="control">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                </div>
            </div>

            <div class="field">
                <label for="password">كلمة المرور</label>
                <div class="control">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <input id="password" type="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="toggle-pass" onclick="togglePassword()" title="إظهار كلمة المرور">
                        <svg id="eye-icon" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    </button>
                </div>
            </div>

            <div class="row">
                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    تذكرني
                </label>
            </div>

            <button type="submit" class="submit">تسجيل الدخول</button>
        </form>

        <div class="creds">
            <h2>
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                بيانات الدخول التجريبية — اضغط للنسخ والتعبئة
            </h2>
            <div class="cred-line">
                <span>البريد الإلكتروني:</span>
                <button type="button" class="cred-value" onclick="fillCred('email', 'admin@daftar.test')">admin@daftar.test</button>
            </div>
            <div class="cred-line">
                <span>كلمة المرور:</span>
                <button type="button" class="cred-value" onclick="fillCred('password', 'password')">password</button>
            </div>
        </div>

        <p class="footer">دفتر © {{ date('Y') }} — جميع الحقوق محفوظة</p>
    </main>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function fillCred(field, value) {
            document.getElementById(field).value = value;
            navigator.clipboard?.writeText(value);
        }
    </script>
</body>
</html>
