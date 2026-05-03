<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
</head>
<body style="margin:0; padding:0; background-color:#0f172a; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f172a; min-height:100vh;">
        <tr>
            <td align="center" style="padding: 40px 16px;">

                <!-- Main Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#1e293b; border-radius:16px; overflow:hidden; border:1px solid #334155;">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); padding:32px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <!-- Logo/Icon -->
                                        <div style="width:56px; height:56px; background-color:rgba(255,255,255,0.2); border-radius:16px; text-align:center; line-height:56px; margin-bottom:16px;">
                                            <span style="font-size:28px;">📊</span>
                                        </div>
                                        <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700;">Journal Trading Connect</h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0">

                                <!-- Greeting -->
                                <tr>
                                    <td style="padding-bottom:8px;">
                                        <h2 style="margin:0; color:#f1f5f9; font-size:20px; font-weight:600;">Halo, {{ $user->name }}! 👋</h2>
                                    </td>
                                </tr>

                                <!-- Message -->
                                <tr>
                                    <td style="padding-bottom:24px;">
                                        <p style="margin:0; color:#94a3b8; font-size:15px; line-height:1.6;">
                                            Terima kasih sudah mendaftar di <strong style="color:#e2e8f0;">Journal Trading Connect</strong>. Untuk mengaktifkan akun kamu, silakan verifikasi email dengan mengklik tombol di bawah ini.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Button -->
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" style="border-radius:10px;">
                                                    <a href="{{ $verificationUrl }}" target="_blank" style="display:inline-block; background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%); color:#ffffff; text-decoration:none; font-size:15px; font-weight:600; padding:14px 32px; border-radius:10px; min-width:240px; text-align:center;">
                                                        ✅ Verifikasi Email
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Divider -->
                                <tr>
                                    <td style="padding-bottom:20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="border-top:1px solid #334155; font-size:0; height:1px;">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Info -->
                                <tr>
                                    <td style="padding-bottom:6px;">
                                        <p style="margin:0; color:#64748b; font-size:13px; line-height:1.6;">
                                            📧 Email terdaftar: <strong style="color:#94a3b8;">{{ $user->email }}</strong>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding-bottom:20px;">
                                        <p style="margin:0; color:#64748b; font-size:13px; line-height:1.6;">
                                            ⏱️ Link ini berlaku selama <strong style="color:#94a3b8;">60 menit</strong>
                                        </p>
                                    </td>
                                </tr>

                                <!-- Warning -->
                                <tr>
                                    <td style="background-color:#1a1f2e; border-radius:10px; padding:16px; border:1px solid #334155;">
                                        <p style="margin:0; color:#94a3b8; font-size:13px; line-height:1.6;">
                                            ⚠️ <strong style="color:#e2e8f0;">Perhatian:</strong> Jika kamu tidak merasa mendaftar, abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.
                                        </p>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 24px; border-top:1px solid #334155; background-color:#1a2235;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <p style="margin:0; color:#475569; font-size:12px; line-height:1.5;">
                                            © {{ date('Y') }} Journal Trading Connect. All rights reserved.
                                        </p>
                                        <p style="margin:8px 0 0 0; color:#475569; font-size:12px;">
                                            <a href="{{ config('app.url') }}" style="color:#6366f1; text-decoration:none;">eatrade-journal.site</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
