<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode Verifikasi - Midnight Finance</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #020617; /* Warna Slate-950 */
            color: #f8fafc; /* Warna Slate-50 */
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            background-color: #0f172a; /* Warna Slate-900 */
            padding: 40px;
            border-radius: 12px;
            border: 1px solid #1e293b; /* Warna Slate-800 */
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #f59e0b; /* Warna Amber-500 */
            margin: 0;
            font-size: 24px;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-style: italic;
        }
        .content {
            text-align: left;
        }
        .content p {
            font-size: 15px;
            line-height: 1.6;
            color: #cbd5e1; /* Warna Slate-300 */
            margin-bottom: 20px;
        }
        .otp-wrapper {
            text-align: center;
            margin: 40px 0;
        }
        .otp-box {
            display: inline-block;
            background-color: #020617;
            border: 1px solid #f59e0b;
            padding: 20px 40px;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 12px;
            color: #f59e0b;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.1);
        }
        .warning {
            background-color: rgba(225, 29, 72, 0.1); /* Warna Rose transparant */
            border-left: 4px solid #e11d48; /* Warna Rose-600 */
            padding: 15px;
            margin-top: 30px;
        }
        .warning p {
            color: #f43f5e;
            font-size: 13px;
            margin: 0;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #64748b; /* Warna Slate-500 */
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MIDNIGHT FINANCE</h1>
        </div>

        <div class="content">
            <p>Yth. <strong>{{ $user->name }}</strong>,</p>

            <p>Sistem kami menerima permintaan otorisasi untuk mengakses brankas digital Anda. Berikut adalah kode verifikasi keamanan (OTP) yang diperlukan untuk melanjutkan proses tersebut:</p>

            <div class="otp-wrapper">
                <div class="otp-box">
                    {{ $otp }}
                </div>
            </div>

            <p>Demi keamanan aset Anda, kode ini hanya berlaku selama <strong>10 menit</strong> sejak email ini dikirimkan.</p>

            <div class="warning">
                <p>PERINGATAN KEAMANAN: Jangan pernah memberikan kode ini kepada siapa pun. Pihak Midnight Finance tidak akan pernah meminta kode verifikasi Anda dengan alasan apa pun.</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Midnight Private Wealth. Hak cipta dilindungi undang-undang.</p>
            <p>Pesan ini dihasilkan secara otomatis oleh sistem keamanan internal. Mohon untuk tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
