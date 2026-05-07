# SSL/HTTPS Setup with Certbot + Nginx (Let's Encrypt)

## Prerequisites

1. **Domain** pointing to VPS via A Record (both `@` and `www`)
2. **Nginx** server block already configured and serving the site on port 80
3. **No other service** occupying port 80 on the same `server_name`

## Install Certbot

```bash
sudo apt update
sudo apt install -y certbot python3-certbot-nginx
```

This also creates a systemd timer for auto-renewal (`certbot.timer`).

## Nginx Config for Domain (HTTP first)

Create `/etc/nginx/sites-available/mydomain`:

```nginx
server {
    listen 80;
    server_name mydomain.com www.mydomain.com;

    root /home/ubuntu/myproject/public;
    index index.php;
    charset utf-8;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and verify:

```bash
sudo ln -sf /etc/nginx/sites-available/mydomain /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
curl -sI http://mydomain.com/  # Expect 200 or 302
```

## Generate SSL Certificate

```bash
sudo certbot --nginx -d mydomain.com -d www.mydomain.com \
  --non-interactive --agree-tos --email admin@mydomain.com
```

Certbot will:
- Verify domain ownership via `.well-known/acme-challenge/` HTTP request
- Automatically modify the Nginx config to add SSL directives
- Set up HTTP→HTTPS redirect
- Configure auto-renewal via `certbot.timer`

## Verify HTTPS

```bash
curl -sI https://mydomain.com/  # Expect 200 or 302 with HTTP/2
sudo certbot certificates          # Show cert expiry + auto-renewal status
```

## Multiple Domains/Subdomains

Each project gets its own Nginx config + cert:

```bash
# JTC on main domain
sudo certbot --nginx -d eatrade-journal.site -d www.eatrade-journal.site ...

# Monitor on subdomain (needs own server block + A record first)
sudo certbot --nginx -d monitor.eatrade-journal.site ...
```

## Auto-Renewal

Certbot installs a systemd timer that runs twice daily. Verify:

```bash
sudo systemctl status certbot.timer  # Should be active
sudo certbot renew --dry-run         # Test renewal without actually renewing
```

## PITFALL: DNS resolves to multiple IPs

If `nslookup mydomain.com` returns two IPs (e.g., your VPS + a CDN/proxy), Let's Encrypt challenge may hit the wrong IP and fail with `unauthorized` / `Invalid response`.

**Fix:** Remove the extra A record so only your VPS IP remains. Then retry certbot.

```bash
nslookup mydomain.com 8.8.8.8  # Should return ONLY your VPS IP
```

## PITFALL: DNS nameservers still pointing to parking provider

Even if you add correct A records at your DNS provider, the domain won't resolve correctly if the **nameservers** still point to a parking service. The A records at the parking provider override your new DNS provider's records.

**Symptoms:**
- `nslookup mydomain.com NS` returns names like `byte.dns-parking.com`, `pixel.dns-parking.com`, or similar parking NS
- A records you added at your DNS provider (Hostinger, Cloudflare, etc.) don't take effect
- `dig mydomain.com A` returns 2 IPs: your VPS + a parking IP (e.g., `2.57.91.91`)

**Fix:** Change the domain's nameservers at the **registrar** (where you bought the domain) to your DNS provider's nameservers:
- **Hostinger:** `ns1.hostinger.com`, `ns2.hostinger.com` (check your panel for exact values — they vary by region/datacenter)
- **Cloudflare:** shown in dashboard → overview
- **Other providers:** check their documentation

**Workflow:**
1. User adds A records at DNS provider → ❌ insufficient if NS still parked
2. User changes NS at registrar to DNS provider's NS → DNS starts working
3. Verify with `dig mydomain.com NS +short` → should show your DNS provider's NS, not parking
4. Wait for propagation (can take 1-24 hours globally)
5. Only then run certbot

**Key lesson:** Always check `NS` records BEFORE checking `A` records. NS determines which DNS server is authoritative — A records at a non-authoritative DNS provider are ignored.

## PITFALL: Port 80 already in use by another server block

If you have a default `server_name _;` block on port 80, it may intercept requests for the new domain before the domain-specific block. Nginx picks the most specific `server_name` match, but if the default block has no `server_name` at all, it becomes the catch-all.

## PITFALL: `.well-known` blocked by Nginx location rule

The location rule `location ~ /\.(?!well-known).* { deny all; }` allows `.well-known` through (negative lookahead). If your Nginx config is missing this exception, certbot challenges will fail with 404.

## PITFALL: `sudo nginx -t` fails with "Permission denied"

On Ubuntu, `nginx -t` as non-root user can't access the PID file. Always use `sudo`:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Testing After Setup

1. `curl -sI https://mydomain.com/` — should show `200` or `302` with `HTTP/2`
2. `curl -sI http://mydomain.com/` — should show `301` or `308` redirect to HTTPS
3. Browser dev tools → Security tab should show "Secure connection"
4. Laravel `.env`: update `APP_URL=https://mydomain.com` after SSL is active

## Path-Based App Behind SSL (Combined Config)

When serving a second Laravel app via path prefix (e.g., `site.com/monitor`) behind the same SSL cert:

```nginx
server {
    server_name eatrade-journal.site www.eatrade-journal.site;

    root /home/ubuntu/journal-trading-connect/public;
    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    # Main app (PHP-FPM)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Second app (proxy to its own port — NO rewrite, pass prefix as-is)
    location /monitor {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Port 443;
        proxy_set_header Accept-Encoding "";
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    location ~ /\.(?!well-known).* { deny all; }

    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/eatrade-journal.site/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/eatrade-journal.site/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

# HTTP → HTTPS redirect (auto-generated by certbot)
server {
    listen 80;
    server_name eatrade-journal.site www.eatrade-journal.site;
    return 404;
}
```

Key: both apps share the same SSL cert. No separate cert needed for the path-based app. See SKILL.md "Path-Based Reverse Proxy" section for the Laravel-side route prefix setup.
