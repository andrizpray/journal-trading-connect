---
name: jtc-email-smtp
title: JTC Email & SMTP Configuration
description: JTC email verification setup, SMTP SumoPod config, and custom dark-themed templates.
---

# JTC Email & SMTP Configuration

## SMTP Provider: SumoPod
- **Host:** smtp.sumopod.com
- **Port:** 465 (SSL)
- **Encryption:** SSL
- **Any sender OK:** Yes, can use any FROM address

## JTC Laravel Config (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sumopod.com
MAIL_PORT=465
MAIL_USERNAME=null   # Any sender OK
MAIL_PASSWORD=null   # Any sender OK
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@eatrade-journal.site
MAIL_FROM_NAME="Journal Trading Connect"
```

## Email Verification Setup
- **User model:** `MustVerifyEmail` interface implemented
- **Middleware:** `verified` middleware on dashboard routes
- **Custom template:** Dark-themed email verification (gradient header)
- **Commit:** 886a605

## Custom Email Template
Created custom dark-themed email verification template:
- Dark gradient header (#1e293b → #0f172a)
- Matches JTC's Tailwind dark theme
- Commit: 886a605

## Testing
After config, test with:
```bash
php artisan tinker
>>> \Mail::raw('Test', fn($msg) => $msg->to('test@example.com')->subject('Test'));
```
