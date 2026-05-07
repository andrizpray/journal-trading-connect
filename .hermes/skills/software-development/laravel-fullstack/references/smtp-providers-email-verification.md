# SMTP Providers for Laravel Email Verification

Laravel's email verification (via Breeze + `MustVerifyEmail`) requires a working SMTP connection. Without it, verification emails fail silently or throw connection errors.

## Common SMTP Providers

### SumoPod
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.sumopod.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=<provided_username>
MAIL_PASSWORD=<provided_password>
MAIL_FROM_ADDRESS=anything@yourdomain.com  # SumoPod allows any sender address
MAIL_FROM_NAME="Your App"
```
- SSL on port 465. SumoPod allows **any sender address** (`from` field) — no custom domain setup required.
- Credentials are provided on the SumoPod dashboard (username + password).

### Gmail (App Password)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=<16-char-app-password>
```
- Requires 2FA enabled on the Google account
- App Password created at https://myaccount.google.com/apppasswords
- App Password only shown once — if lost, must regenerate
- Limit: ~500 emails/day

### Resend (Free tier: 100 emails/day)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=resend
MAIL_PASSWORD=<API_key>
```

## Verification Steps (Any Provider)

1. Add SMTP vars to `.env`
2. `php artisan config:cache`
3. Test with tinker:
```bash
php artisan tinker --execute="
\Mail::raw('Test SMTP', function(\$message) {
    \$message->to('recipient@example.com')->subject('Test');
    echo 'Sent!';
});
"
```
4. If "Sent!" appears, SMTP is working. Check the recipient's inbox/spam.

## Pitfall: `MAIL_FROM_ADDRESS` must match the provider's allowed sender

Most providers (Gmail) only allow sending from addresses associated with the account. If `MAIL_FROM_ADDRESS=noreply@yourdomain.com` but the provider doesn't recognize that domain, emails silently fail or get rejected.

**Exception:** SumoPod allows any sender address without domain verification. You can use `MAIL_FROM_ADDRESS=noreply@yourdomain.com` immediately.

## Pitfall: `MAIL_ENCRYPTION` vs `MAIL_SCHEME`

Laravel 11+ supports `MAIL_SCHEME` (in config/mail.php) alongside `MAIL_ENCRYPTION` (in .env). For most providers, setting `MAIL_ENCRYPTION=ssl` in `.env` is sufficient. If emails fail with TLS errors, try `tls` instead of `ssl` (TLS uses port 587, SSL uses port 465).
