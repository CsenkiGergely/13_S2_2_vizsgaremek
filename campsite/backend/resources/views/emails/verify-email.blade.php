<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Megerősítés</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #4A7434 0%, #5a8a44 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #4A7434;
            font-size: 24px;
            margin-top: 0;
        }
        .content p {
            margin: 15px 0;
            color: #555;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background-color: #4A7434;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .button:hover {
            background-color: #F17E21;
        }
        .info-box {
            background-color: #d4edda;
            border-left: 4px solid #4A7434;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #155724;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .link-text {
            color: #4A7434;
            word-break: break-all;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CampSite</h1>
        </div>

        <div class="content">
            <h2>Üdvözlünk, {{ trim(($user->owner_last_name ?? '') . ' ' . ($user->owner_first_name ?? '')) ?: ($user->email ?? 'Felhasználó') }}! 🏕️</h2>

            <p>Köszönjük, hogy regisztráltál a CampSite-on!</p>

            <p>Biztonsági okokból kérjük, erősítsd meg az email címedet az alábbi gombra kattintva:</p>

            <div class="button-container">
                <a href="{{ $verifyUrl }}" class="button">Email Megerősítése</a>
            </div>

            <div class="info-box">
                <p><strong>⏰ Fontos:</strong> Ez a link 24 óráig érvényes.</p>
            </div>

            <p>Ha a gomb nem működik, másold be ezt a linket a böngésződbe:</p>
            <p class="link-text">{{ $verifyUrl }}</p>

            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                Ha nem te regisztráltál, kérjük hagyd figyelmen kívül ezt az üzenetet.
            </p>
        </div>

        <div class="footer">
            <p><strong>CampSite</strong> - A legjobb kempingek egy helyen</p>
            <p>Ez egy automatikus üzenet, kérjük ne válaszolj rá.</p>
        </div>
    </div>
</body>
</html>
