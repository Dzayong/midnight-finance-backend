<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Atur Ulang Kata Sandi - Midnight Finance</title>
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
        .btn-wrapper {
            text-align: center;
            margin: 40px 0;
        }
        .btn {
            display: inline-block;
            background-color: #f59e0b; /* Warna Emas Utama */
            color: #020617; /* Teks Gelap */
            padding: 16px 32px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .link-fallback {
            font-size: 12px;
            color: #64748b;
            margin-top: 20px;
            word-break: break-all;
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

            <p>Sistem kami menerima permintaan untuk mengatur ulang kata sandi brankas digital Anda. Jika ini memang Anda, silakan klik tombol di bawah ini untuk membuat kata sandi baru:</p>

            <div class="btn-wrapper">
                <a href="{{ $resetUrl }}" class="btn">Atur Ulang Kata Sandi</a>
            </div>

            <div class="link-fallback">
                <p>Jika tombol di atas tidak berfungsi, salin dan tempel tautan berikut di *browser* Anda:</p>
                <p>{{ $resetUrl }}</p>
            </div>

            <div class="warning">
                <p>PERINGATAN: Jika Anda tidak pernah meminta pengaturan ulang kata sandi, abaikan email ini. Tautan ini hanya berlaku selama 10 menit demi keamanan aset Anda.</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Midnight Private Wealth. Hak cipta dilindungi undang-undang.</p>
            <p>Pesan ini dihasilkan secara otomatis oleh sistem keamanan internal. Mohon untuk tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
