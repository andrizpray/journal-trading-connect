# MQL5 WebRequest Pitfalls (EA-Side)

Common traps when developing `.mq4`/`.mq5` EA files that communicate with a Laravel API.

## Hardcoded POST Method

`WebRequest()` method is a string parameter — don't hardcode `"POST"`. Use a method parameter:

```mql5
// WRONG — ping endpoint is GET, but always sends POST
string SendRequest(string endpoint, string jsonBody) {
    int res = WebRequest("POST", url, headers, timeout, dataOut, dataIn, responseHeaders);
}

// CORRECT — method is configurable
string SendRequest(string endpoint, string jsonBody="", string method="POST") {
    uchar dataOut[];
    int bodyLen = 0;
    if(StringLen(jsonBody) > 0) {
        bodyLen = StringToCharArray(jsonBody, dataOut, 0, WHOLE_ARRAY, CP_UTF8) - 1;
        if(bodyLen < 0) bodyLen = 0;
        ArrayResize(dataOut, bodyLen);
    }
    int res = WebRequest(method, url, headers, timeout, dataOut, dataIn, responseHeaders);
}

// Usage:
SendRequest("/api/ea/ping", "", "GET");       // GET, no body
SendRequest("/api/ea/trade/batch", json);      // POST with body (default)
```

## StringToCharArray Null Terminator

`StringToCharArray()` appends a null terminator byte. If you don't resize, the server receives `\0` at end of JSON body, causing parse errors.

```mql5
// WRONG — includes null terminator
StringToCharArray(jsonBody, dataOut, 0, StringLen(jsonBody));

// CORRECT — trim the null terminator
int bodyLen = StringToCharArray(jsonBody, dataOut, 0, WHOLE_ARRAY, CP_UTF8) - 1;
if(bodyLen < 0) bodyLen = 0;
ArrayResize(dataOut, bodyLen);
```

## Headers Missing \r\n on Last Line

Every header line MUST end with `\r\n`, including the last one.

```mql5
// WRONG — last header has no \r\n
string headers = "Content-Type: application/json\r\n"
               + "Authorization: Bearer " + token;

// CORRECT — every header ends with \r\n
string headers = "Content-Type: application/json\r\n"
               + "Authorization: Bearer " + token + "\r\n"
               + "Accept: application/json\r\n";
```

## Content-Length Header for POST

For POST with body, add `Content-Length`. Some servers reject without it.

```mql5
string headers = "Content-Type: application/json\r\n"
               + "Authorization: Bearer " + token + "\r\n"
               + "Accept: application/json\r\n";
if(bodyLen > 0)
    headers += "Content-Length: " + IntegerToString(bodyLen) + "\r\n";
```

## WebRequest Error Codes

| Code | Meaning | Fix |
|------|---------|-----|
| 4014 | DNS resolution failed | Check URL, internet, flush DNS (`ipconfig /flushdns`), switch DNS to 8.8.8.8 |
| 4060 | URL not in AllowWebRequest whitelist | Tools → Options → Expert Advisors → add URL |
| 4024 | Internal WebRequest error | Restart terminal, check MT5 version |
| 4073 | Timeout | Increase timeout or check server |

**Error 4014 (DNS)** is commonly misdiagnosed — can also appear when domain NS records point to a parking service. User should verify DNS from their machine and switch to Google DNS (8.8.8.8) or Cloudflare (1.1.1.1) if needed.

## trade_type Logic for MT5 Deal Entry

In MT5, deal OUT has **opposite** type from the original position:
- Position was BUY → closing deal has `DEAL_TYPE_SELL` (you sell to close)
- Position was SELL → closing deal has `DEAL_TYPE_BUY` (you buy to close)

```mql5
// WRONG — treats deal OUT type as the trade direction
string tradeType = (dealType == DEAL_TYPE_BUY) ? "buy" : "sell";

// CORRECT — deal OUT type is opposite of position direction
string tradeType = (dealType == DEAL_TYPE_SELL) ? "buy" : "sell";
```

## HTTP 201 (Created) Response

Laravel's `Model::create()` can return 201. EA should accept both 200 and 201:

```mql5
// WRONG — only accepts 200
if(res != 200) { return ""; }

// CORRECT — accept 200 and 201
if(res != 200 && res != 201) { return ""; }
```

## URL Trailing Slash

Always strip trailing slash from server URL to avoid double-slash in endpoints:

```mql5
string StripTrailingSlash(string url) {
    while(StringLen(url) > 0 && StringGetCharacter(url, StringLen(url) - 1) == '/')
        url = StringSubstr(url, 0, StringLen(url) - 1);
    return url;
}

// In OnInit():
g_serverUrl = StripTrailingSlash(InpServerUrl);
```

## HistorySelect Before Deal Loop

MT5 requires `HistorySelect()` to be called before iterating deals. Without it, `HistoryDealsTotal()` returns 0:

```mql5
// In OnTimer():
HistorySelect(0, TimeCurrent());
int totalDeals = HistoryDealsTotal();
```

Also call inside `GetSLTPFromOrder()` before iterating orders, since inner loops can invalidate the history selection.
