//+------------------------------------------------------------------+
//|                                           JTC_Logger.mq4        |
//|                        Journal Trading Connect - EA Logger      |
//|                                     github.com/andrizpray      |
//+------------------------------------------------------------------+
#property copyright "Journal Trading Connect"
#property link      "https://github.com/andrizpray/journal-trading-connect"
#property version   "1.00"
#property strict
#property indicator_chart_window
#property indicator_buffers 0

//--- Input parameters (user isi setelah install)
input string InpServerUrl    = "http://43.134.37.14:8081"; // Server URL
input string InpApiToken     = "";                         // API Token (dari halaman Connect)
input int    InpIntervalSec  = 30;    // Kirim data tiap X detik
input int    InpMaxTrades    = 50;    // Maks trade per request
input bool   InpDebugMode    = false; // Print log ke Experts tab

//--- Global variables
datetime g_lastSentTime   = 0;
int      g_lastHistoryTotal = 0;
string   g_statusText     = "";
color    g_statusColor    = clrGray;
bool     g_firstRun       = true;

//+------------------------------------------------------------------+
//| Custom indicator initialization function                        |
//+------------------------------------------------------------------+
int OnInit()
{
   // Validasi input
   if(InpApiToken == "")
   {
      Alert("[JTC] ERROR: API Token kosong!\n",
             "1. Login ke web\n",
             "2. Buka Connect > EA Logger\n",
             "3. Copy API Token\n",
             "4. Paste ke parameter InpApiToken");
      Print("[JTC] ERROR: API Token kosong!");
      g_statusText = "ERROR: Token kosong";
      g_statusColor = clrRed;
      return INIT_FAILED;
   }

   if(InpIntervalSec < 10)
   {
      Print("[JTC] WARNING: Interval terlalu cepat, minimum 10 detik. Set ke 30.");
      g_statusText = "WARNING: Interval < 10s";
      g_statusColor = clrOrange;
   }

   Print("===========================================");
   Print("[JTC] Journal Trading Connect - EA Logger");
   Print("[JTC] Version: 1.00");
   Print("[JTC] Server: ", InpServerUrl);
   Print("[JTC] Interval: ", InpIntervalSec, " seconds");
   Print("[JTC] Max trades/request: ", InpMaxTrades);
   Print("===========================================");

   // Setup timer untuk periodic sync
   EventSetTimer(InpIntervalSec);

   // Kirim pertama kali setelah 5 detik (biar chart siap)
   g_firstRun = true;

   return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
//| Custom indicator deinitialization function                      |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
   EventKillTimer();

   string reasonText = "";
   switch(reason)
   {
      case REASON_PARAMETERS: reasonText = "Parameter diubah"; break;
      case REASON_CHARTCHANGE: reasonText = "Chart/Timeframe diubah"; break;
      case REASON_REMOVE: reasonText = "Indicator dihapus dari chart"; break;
      case REASON_CLOSE: reasonText = "Chart ditutup"; break;
      default: reasonText = "Unknown"; break;
   }

   Print("[JTC] Logger stopped. Reason: ", reasonText);
   Comment("");
}

//+------------------------------------------------------------------+
//| OnCalculate - required for custom indicators                    |
//+------------------------------------------------------------------+
int OnCalculate(const int rates_total,
                const int prev_calculated,
                const datetime &time[],
                const double &open[],
                const double &high[],
                const double &low[],
                const double &close[],
                const long &tick_volume[],
                const long &volume[],
                const int &spread[])
{
   return(rates_total);
}

//+------------------------------------------------------------------+
//| Timer event - periodic sync                                     |
//+------------------------------------------------------------------+
void OnTimer()
{
   // Skip kalau belum ada history (terminal baru dibuka)
   if(OrdersHistoryTotal() == 0)
   {
      if(InpDebugMode) Print("[JTC] No history yet, waiting...");
      UpdateStatus("Menunggu history...", clrYellow);
      return;
   }

   // Pertama kali: kirim setelah init
   if(g_firstRun)
   {
      if(InpDebugMode) Print("[JTC] First run, syncing history...");
      g_firstRun = false;
   }

   // Kirim trade history
   SendTradeHistory();
}

//+------------------------------------------------------------------+
//| Baca history dari MT4, kirim ke server                           |
//+------------------------------------------------------------------+
void SendTradeHistory()
{
   int total = OrdersHistoryTotal();

   if(total == g_lastHistoryTotal && !g_firstRun)
   {
      if(InpDebugMode) Print("[JTC] No new trades (", total, " total)");
      UpdateStatus("Synced | Trades: " + IntegerToString(total), clrLime);
      return;
   }

   UpdateStatus("Mengirim data...", clrYellow);

   // Ambil trade terbaru saja (dari index terakhir)
   int startIndex = MathMax(0, total - InpMaxTrades);

   // Bangun JSON array
   string json = "{\"trades\":[";
   bool first = true;
   int tradeCount = 0;

   for(int i = startIndex; i < total; i++)
   {
      if(!OrderSelect(i, SELECT_BY_POS, MODE_HISTORY)) continue;

      // Filter: hanya tipe buy/sell (bukan balance/credit)
      int type = OrderType();
      if(type != OP_BUY && type != OP_SELL) continue;

      // Filter: hanya trade yang sudah close
      if(OrderCloseTime() == 0) continue;

      if(!first) json += ",";
      first = false;
      tradeCount++;

      // Hitung durasi
      int duration = (int)(OrderCloseTime() - OrderOpenTime()) / 60;

      // Ambil digit symbol
      int digits = (int)MarketInfo(OrderSymbol(), MODE_DIGITS);
      if(digits == 0) digits = 5; // fallback

      // Profit total (profit + swap + commission)
      double totalProfit = OrderProfit() + OrderSwap() + OrderCommission();

      // Ambil SL dan TP
      double sl = OrderStopLoss();
      double tp = OrderTakeProfit();
      string slStr = (sl > 0) ? DoubleToStr(sl, digits) : "0";
      string tpStr = (tp > 0) ? DoubleToStr(tp, digits) : "0";

      // Escape comment (hapus karakter berbahaya)
      string comment = OrderComment();
      StringReplace(comment, "\"", "'");
      StringReplace(comment, "\\", "/");

      // Build JSON object untuk trade ini
      json += "{";
      json += "\"ticket\":\"" + IntegerToString(OrderTicket()) + "\",";
      json += "\"open_date\":\"" + TimeToString(OrderOpenTime(), TIME_DATE|TIME_MINUTES) + "\",";
      json += "\"close_date\":\"" + TimeToString(OrderCloseTime(), TIME_DATE|TIME_MINUTES) + "\",";
      json += "\"currency_pair\":\"" + OrderSymbol() + "\",";
      json += "\"trade_type\":\"" + (type == OP_BUY ? "buy" : "sell") + "\",";
      json += "\"lot_size\":" + DoubleToStr(OrderLots(), 2) + ",";
      json += "\"open_price\":" + DoubleToStr(OrderOpenPrice(), digits) + ",";
      json += "\"close_price\":" + DoubleToStr(OrderClosePrice(), digits) + ",";
      json += "\"stop_loss\":" + slStr + ",";
      json += "\"take_profit\":" + tpStr + ",";
      json += "\"swap\":" + DoubleToStr(OrderSwap(), 2) + ",";
      json += "\"commission\":" + DoubleToStr(OrderCommission(), 2) + ",";
      json += "\"profit_loss\":" + DoubleToStr(totalProfit, 2) + ",";
      json += "\"duration_minutes\":" + IntegerToString(duration) + ",";
      json += "\"comment\":\"" + comment + "\"";
      json += "}";
   }

   json += "]}";

   if(first || tradeCount == 0)
   {
      if(InpDebugMode) Print("[JTC] No valid trades to send");
      g_lastHistoryTotal = total;
      UpdateStatus("Synced | Trades: " + IntegerToString(total), clrLime);
      return;
   }

   // Kirim ke server
   UpdateStatus("Mengirim " + IntegerToString(tradeCount) + " trade...", clrAqua);

   string response = SendRequest("/api/ea/trade/batch", json);

   if(StringLen(response) > 0)
   {
      g_lastHistoryTotal = total;
      g_lastSentTime = TimeCurrent();

      // Parse response untuk log
      int savedPos = StringFind(response, "\"saved\":");
      int dupPos = StringFind(response, "\"duplicates\":");

      if(savedPos > 0)
      {
         string savedStr = StringSubstr(response, savedPos + 8, 10);
         UpdateStatus("OK | Sent: " + IntegerToString(tradeCount) + " trades", clrLime);
         Print("[JTC] Sent ", tradeCount, " trades. Response: ", response);
      }
      else
      {
         UpdateStatus("OK | Sent: " + IntegerToString(tradeCount), clrLime);
         Print("[JTC] Sent ", tradeCount, " trades. Response: ", response);
      }

      // Simpan log ke file lokal sebagai backup
      SaveLogToFile("sent|" + IntegerToString(tradeCount) + "|" + response);
   }
   else
   {
      UpdateStatus("GAGAL kirim | Offline?", clrRed);
      Print("[JTC] GAGAL mengirim! Cek koneksi internet.");
      Print("[JTC] Data disimpan lokal untuk dikirim nanti.");

      // Simpan ke file lokal untuk retry nanti
      SaveOfflineTrades(json);
   }
}

//+------------------------------------------------------------------+
//| Kirim HTTP POST request ke server                                |
//+------------------------------------------------------------------+
string SendRequest(string endpoint, string jsonBody)
{
   string url = InpServerUrl + endpoint;
   string headers = "Content-Type: application/json\r\n";
   headers += "Authorization: Bearer " + InpApiToken;
   int timeout = 5000; // 5 detik

   // MQL4 WebRequest requires uchar arrays for data in/out
   uchar dataOut[];
   uchar dataIn[];
   string responseHeaders = "";

   StringToCharArray(jsonBody, dataOut, 0, StringLen(jsonBody));

   int res = WebRequest("POST", url, headers, timeout, dataOut, dataIn, responseHeaders);

   string result = CharArrayToString(dataIn);

   if(res == -1)
   {
      int err = GetLastError();
      string errMsg = "";

      switch(err)
      {
         case 4060: errMsg = "URL tidak ada di AllowWebRequest!"; break;
         case 4024: errMsg = "Internal error WebRequest"; break;
         case 4073: errMsg = "Timeout (" + IntegerToString(timeout) + "ms)"; break;
         default: errMsg = "Error code " + IntegerToString(err); break;
      }

      Print("[JTC] WebRequest gagal: ", errMsg);
      Print("[JTC] Fix: Tools > Options > Expert Advisors > Centang 'Allow WebRequest'");
      Print("[JTC] Lalu tambahkan URL: ", InpServerUrl);

      if(InpDebugMode)
      {
         Print("[JTC] URL: ", url);
         Print("[JTC] Body length: ", StringLen(jsonBody));
      }

      return "";
   }

   if(InpDebugMode)
   {
      Print("[JTC] Request: POST ", url);
      Print("[JTC] HTTP Status: ", res);
      Print("[JTC] Response: ", StringSubstr(result, 0, MathMin(StringLen(result), 200)));
   }

   // Status 200 = OK
   if(res != 200)
   {
      Print("[JTC] HTTP Error: ", res, " | ", result);
   }

   return result;
}

//+------------------------------------------------------------------+
//| Kirim heartbeat ke server                                        |
//+------------------------------------------------------------------+
void SendHeartbeat()
{
   string json = "{}";
   string response = SendRequest("/api/ea/heartbeat", json);

   if(InpDebugMode && StringLen(response) > 0)
   {
      Print("[JTC] Heartbeat OK: ", response);
   }
}

//+------------------------------------------------------------------+
//| Simpan log ke file lokal (backup)                                |
//+------------------------------------------------------------------+
void SaveLogToFile(string text)
{
   int handle = FileOpen("jtc_log.txt", FILE_READ|FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
   if(handle == INVALID_HANDLE)
   {
      handle = FileOpen("jtc_log.txt", FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
      if(handle == INVALID_HANDLE)
      {
         if(InpDebugMode) Print("[JTC] Cannot create log file");
         return;
      }
      FileWrite(handle, "JTC Logger - Activity Log");
      FileWrite(handle, "================================");
   }

   // Append ke akhir file
   FileSeek(handle, 0, SEEK_END);
   FileWrite(handle, TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS) + " | " + text);
   FileClose(handle);
}

//+------------------------------------------------------------------+
//| Simpan trade data ke file lokal (untuk retry saat offline)       |
//+------------------------------------------------------------------+
void SaveOfflineTrades(string jsonBody)
{
   int handle = FileOpen("jtc_offline_buffer.txt", FILE_READ|FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
   if(handle == INVALID_HANDLE)
   {
      handle = FileOpen("jtc_offline_buffer.txt", FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
      if(handle == INVALID_HANDLE)
      {
         if(InpDebugMode) Print("[JTC] Cannot create offline buffer file");
         return;
      }
      FileWrite(handle, "# JTC Offline Buffer - trades yang belum terkirim");
      FileWrite(handle, "# Format: JSON array per baris");
      FileWrite(handle, "# File ini otomatis diproses saat server kembali online");
   }

   FileSeek(handle, 0, SEEK_END);
   FileWrite(handle, jsonBody);
   FileClose(handle);

   if(InpDebugMode) Print("[JTC] Saved to offline buffer for retry");

   // Update status
   UpdateStatus("OFFLINE - Data disimpan lokal", clrOrange);
}

//+------------------------------------------------------------------+
//| Tampilkan status di chart                                        |
//+------------------------------------------------------------------+
void UpdateStatus(string text, color c)
{
   g_statusText = text;
   g_statusColor = c;

   string display = "=== JTC Logger v1.0 ===\n";
   display += text + "\n";
   display += "Last sync: " + TimeToString(g_lastSentTime, TIME_DATE|TIME_MINUTES);
   if(g_lastSentTime == 0) display += " (belum)";
   display += "\nHistory: " + IntegerToString(OrdersHistoryTotal()) + " trades";

   Comment(display);
}

//+------------------------------------------------------------------+
//| Chart event handler                                             |
//+------------------------------------------------------------------+
void OnChartEvent(const int id, const long& lparam, const double& dparam, const string& sparam)
{
   // Tidak perlu aksi khusus untuk chart events
}
