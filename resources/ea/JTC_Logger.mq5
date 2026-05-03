//+------------------------------------------------------------------+
//|                                           JTC_Logger.mq5        |
//|                        Journal Trading Connect - EA Logger      |
//|                                     github.com/andrizpray      |
//+------------------------------------------------------------------+
#property copyright "Journal Trading Connect"
#property link      "https://github.com/andrizpray/journal-trading-connect"
#property version   "1.00"
#property indicator_chart_window
#property indicator_buffers 0
#property indicator_plots   0

//--- Input parameters (user isi setelah install)
input string InpServerUrl    = "http://43.134.37.14:8081"; // Server URL
input string InpApiToken     = "";                         // API Token (dari halaman Connect)
input int    InpIntervalSec  = 30;    // Kirim data tiap X detik
input int    InpMaxTrades    = 50;    // Maks trade per request
input bool   InpDebugMode    = false; // Print log ke Experts tab

//--- Global variables
datetime g_lastSentTime    = 0;
int      g_lastDealCount   = 0;
string   g_statusText      = "";
color    g_statusColor     = clrGray;
bool     g_firstRun        = true;

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
      return INIT_FAILED;
   }

   if(InpIntervalSec < 10)
   {
      Print("[JTC] WARNING: Interval terlalu cepat, minimum 10 detik. Set ke 30.");
   }

   Print("===========================================");
   Print("[JTC] Journal Trading Connect - EA Logger (MT5)");
   Print("[JTC] Version: 1.00");
   Print("[JTC] Server: ", InpServerUrl);
   Print("[JTC] Interval: ", InpIntervalSec, " seconds");
   Print("[JTC] Max trades/request: ", InpMaxTrades);
   Print("===========================================");

   // Setup timer untuk periodic sync
   EventSetTimer(InpIntervalSec);

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
      case REASON_PARAMETERS:  reasonText = "Parameter diubah"; break;
      case REASON_CHARTCHANGE: reasonText = "Chart/Timeframe diubah"; break;
      case REASON_REMOVE:      reasonText = "Indicator dihapus dari chart"; break;
      case REASON_CLOSE:       reasonText = "Chart ditutup"; break;
      default:                 reasonText = "Unknown"; break;
   }

   Print("[JTC] Logger stopped. Reason: ", reasonText);
   Comment("");
}

//+------------------------------------------------------------------+
//| Timer event - periodic sync                                     |
//+------------------------------------------------------------------+
void OnTimer()
{
   // Select all history deals
   HistorySelect(0, TimeCurrent());
   int totalDeals = HistoryDealsTotal();

   if(totalDeals == 0)
   {
      if(InpDebugMode) Print("[JTC] No deals yet, waiting...");
      UpdateStatus("Menunggu history...", clrYellow);
      return;
   }

   if(g_firstRun)
   {
      if(InpDebugMode) Print("[JTC] First run, syncing history...");
      g_firstRun = false;
   }

   SendTradeHistory();
}

//+------------------------------------------------------------------+
//| Baca history deals dari MT5, kirim ke server                    |
//+------------------------------------------------------------------+
void SendTradeHistory()
{
   HistorySelect(0, TimeCurrent());
   int totalDeals = HistoryDealsTotal();

   if(totalDeals == g_lastDealCount && !g_firstRun)
   {
      if(InpDebugMode) Print("[JTC] No new deals (", totalDeals, " total)");
      UpdateStatus("Synced | Deals: " + IntegerToString(totalDeals), clrLime);
      return;
   }

   UpdateStatus("Mengirim data...", clrYellow);

   // Hitung posisi awal (dari deal terbaru)
   int startIndex = MathMax(0, totalDeals - InpMaxTrades);
   // Kita perlu scan lebih banyak karena ada deal entry dan exit
   // Scan dari max(0, total - InpMaxTrades*2) untuk menemukan deal exit
   int scanStart = MathMax(0, totalDeals - (InpMaxTrades * 3));

   // Bangun JSON array
   string json = "{\"trades\":[";
   bool first = true;
   int tradeCount = 0;

   // Kita iterate dari bawah (terlama) ke atas (terbaru)
   // Dan hanya ambil deal bertipe DEAL_ENTRY_IN atau DEAL_ENTRY_OUT
   // Tapi untuk trading journal, kita perlu deal OUT (yang punya profit)
   for(int i = totalDeals - 1; i >= 0 && tradeCount < InpMaxTrades; i--)
   {
      ulong dealTicket = HistoryDealGetTicket(i);
      if(dealTicket == 0) continue;

      // Hanya ambil deal tipe buy/sell (bukan balance, credit, dll)
      long dealEntry = HistoryDealGetInteger(dealTicket, DEAL_ENTRY);
      long dealType  = HistoryDealGetInteger(dealTicket, DEAL_TYPE);

      // Kita hanya proses deal OUT (trade yang sudah ditutup)
      // DEAL_ENTRY_OUT = 1, DEAL_ENTRY_INOUT = 2
      if(dealEntry != DEAL_ENTRY_OUT && dealEntry != DEAL_ENTRY_INOUT) continue;

      // Skip kalau dealType bukan buy/sell
      if(dealType != DEAL_TYPE_BUY && dealType != DEAL_TYPE_SELL) continue;

      // Skip kalau profit = 0 dan komisi = 0 (deal pembukaan)
      double profit = HistoryDealGetDouble(dealTicket, DEAL_PROFIT);
      double swap    = HistoryDealGetDouble(dealTicket, DEAL_SWAP);
      double comm    = HistoryDealGetDouble(dealTicket, DEAL_COMMISSION);
      double volume  = HistoryDealGetDouble(dealTicket, DEAL_VOLUME);

      if(volume == 0) continue;

      if(!first) json += ",";
      first = false;
      tradeCount++;

      // Ambil data deal
      string symbol      = HistoryDealGetString(dealTicket, DEAL_SYMBOL);
      double price       = HistoryDealGetDouble(dealTicket, DEAL_PRICE);
      datetime orderTime = (datetime)HistoryDealGetInteger(dealTicket, DEAL_TIME);
      long positionId    = HistoryDealGetInteger(dealTicket, DEAL_POSITION_ID);

      // Cari deal entry yang berpasangan untuk mendapatkan open_price dan open_time
      double openPrice = 0;
      datetime openTime = orderTime;
      string openComment = "";

      if(positionId > 0)
      {
         HistorySelect(0, TimeCurrent());
         int dealCount = HistoryDealsTotal();
         for(int j = 0; j < dealCount; j++)
         {
            ulong pairedTicket = HistoryDealGetTicket(j);
            if(pairedTicket == 0) continue;

            long pairedPositionId = HistoryDealGetInteger(pairedTicket, DEAL_POSITION_ID);
            long pairedEntry      = HistoryDealGetInteger(pairedTicket, DEAL_ENTRY);

            if(pairedPositionId == positionId && pairedEntry == DEAL_ENTRY_IN)
            {
               openPrice = HistoryDealGetDouble(pairedTicket, DEAL_PRICE);
               openTime  = (datetime)HistoryDealGetInteger(pairedTicket, DEAL_TIME);
               openComment = HistoryDealGetString(pairedTicket, DEAL_COMMENT);
               break;
            }
         }
      }

      // Ambil digits
      int digits = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
      if(digits == 0) digits = 5;

      // Profit total
      double totalProfit = profit + swap + comm;

      // Hitung durasi
      int duration = (int)(orderTime - openTime) / 60;

      // Escape comment
      string comment = HistoryDealGetString(dealTicket, DEAL_COMMENT);
      StringReplace(comment, "\"", "'");
      StringReplace(comment, "\\", "/");

      // Deal type: di MT5, deal OUT berlawanan dengan deal IN
      // Kalau deal OUT type BUY, berarti trade SELL (ditutup buy)
      // Tapi sebenarnya: deal type BUY = kita beli, deal type SELL = kita jual
      string tradeType = (dealType == DEAL_TYPE_BUY) ? "buy" : "sell";

      // Build JSON
      json += "{";
      json += "\"ticket\":\"" + IntegerToString(dealTicket) + "\",";
      json += "\"open_date\":\"" + TimeToString(openTime, TIME_DATE|TIME_MINUTES) + "\",";
      json += "\"close_date\":\"" + TimeToString(orderTime, TIME_DATE|TIME_MINUTES) + "\",";
      json += "\"currency_pair\":\"" + symbol + "\",";
      json += "\"trade_type\":\"" + tradeType + "\",";
      json += "\"lot_size\":" + DoubleToStr(volume, 2) + ",";
      json += "\"open_price\":" + DoubleToStr(openPrice, digits) + ",";
      json += "\"close_price\":" + DoubleToStr(price, digits) + ",";
      json += "\"stop_loss\":0,";
      json += "\"take_profit\":0,";
      json += "\"swap\":" + DoubleToStr(swap, 2) + ",";
      json += "\"commission\":" + DoubleToStr(comm, 2) + ",";
      json += "\"profit_loss\":" + DoubleToStr(totalProfit, 2) + ",";
      json += "\"duration_minutes\":" + IntegerToString(duration) + ",";
      json += "\"comment\":\"" + comment + "\"";
      json += "}";
   }

   json += "]}";

   if(first || tradeCount == 0)
   {
      if(InpDebugMode) Print("[JTC] No valid trades to send");
      g_lastDealCount = totalDeals;
      UpdateStatus("Synced | Deals: " + IntegerToString(totalDeals), clrLime);
      return;
   }

   // Kirim ke server
   UpdateStatus("Mengirim " + IntegerToString(tradeCount) + " trade...", clrAqua);

   string response = SendRequest("/api/ea/trade/batch", json);

   if(StringLen(response) > 0)
   {
      g_lastDealCount = totalDeals;
      g_lastSentTime = TimeCurrent();

      UpdateStatus("OK | Sent: " + IntegerToString(tradeCount) + " trades", clrLime);
      Print("[JTC] Sent ", tradeCount, " trades. Response: ", response);

      SaveLogToFile("sent|" + IntegerToString(tradeCount) + "|" + response);
   }
   else
   {
      UpdateStatus("GAGAL kirim | Offline?", clrRed);
      Print("[JTC] GAGAL mengirim! Cek koneksi internet.");

      SaveOfflineTrades(json);
   }
}

//+------------------------------------------------------------------+
//| Kirim HTTP POST request ke server                                |
//+------------------------------------------------------------------+
string SendRequest(string endpoint, string jsonBody)
{
   string url = InpServerUrl + endpoint;
   string headers = "";
   string result = "";
   string cookie = "";
   int timeout = 5000;

   headers = "Content-Type: application/json\r\n";
   headers += "Authorization: Bearer " + InpApiToken;

   int res = WebRequest("POST", url, headers, timeout, jsonBody, result, cookie);

   if(res == -1)
   {
      int err = GetLastError();
      string errMsg = "";

      switch(err)
      {
         case 4060: errMsg = "URL tidak ada di AllowWebRequest!"; break;
         case 4024: errMsg = "Internal error WebRequest"; break;
         case 4073: errMsg = "Timeout (" + IntegerToString(timeout) + "ms)"; break;
         default:   errMsg = "Error code " + IntegerToString(err); break;
      }

      Print("[JTC] WebRequest gagal: ", errMsg);
      Print("[JTC] Fix: Tools > Options > Expert Advisors");
      Print("[JTC] Centang 'Allow WebRequest' + tambah URL: ", InpServerUrl);

      return "";
   }

   if(InpDebugMode)
   {
      Print("[JTC] Request: POST ", url);
      Print("[JTC] HTTP Status: ", res);
   }

   if(res != 200)
   {
      Print("[JTC] HTTP Error: ", res, " | ", result);
   }

   return result;
}

//+------------------------------------------------------------------+
//| Simpan log ke file lokal                                         |
//+------------------------------------------------------------------+
void SaveLogToFile(string text)
{
   int handle = FileOpen("jtc_log.txt", FILE_READ|FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
   if(handle == INVALID_HANDLE)
   {
      handle = FileOpen("jtc_log.txt", FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
      if(handle == INVALID_HANDLE) return;
      FileWriteString(handle, "JTC Logger (MT5) - Activity Log\r\n");
      FileWriteString(handle, "==================================\r\n");
   }

   FileSeek(handle, 0, SEEK_END);
   FileWriteString(handle, TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS) + " | " + text + "\r\n");
   FileClose(handle);
}

//+------------------------------------------------------------------+
//| Simpan trade data ke file lokal (offline buffer)                 |
//+------------------------------------------------------------------+
void SaveOfflineTrades(string jsonBody)
{
   int handle = FileOpen("jtc_offline_buffer.txt", FILE_READ|FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
   if(handle == INVALID_HANDLE)
   {
      handle = FileOpen("jtc_offline_buffer.txt", FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
      if(handle == INVALID_HANDLE) return;
      FileWriteString(handle, "# JTC Offline Buffer (MT5)\r\n");
   }

   FileSeek(handle, 0, SEEK_END);
   FileWriteString(handle, jsonBody + "\r\n");
   FileClose(handle);

   UpdateStatus("OFFLINE - Data disimpan lokal", clrOrange);
}

//+------------------------------------------------------------------+
//| Tampilkan status di chart                                        |
//+------------------------------------------------------------------+
void UpdateStatus(string text, color c)
{
   g_statusText = text;
   g_statusColor = c;

   string display = "=== JTC Logger v1.0 (MT5) ===\n";
   display += text + "\n";
   display += "Last sync: " + TimeToString(g_lastSentTime, TIME_DATE|TIME_MINUTES);
   if(g_lastSentTime == 0) display += " (belum)";
   display += "\nDeals: " + IntegerToString(HistoryDealsTotal());

   Comment(display);
}

//+------------------------------------------------------------------+
//| Chart event                                                     |
//+------------------------------------------------------------------+
void OnChartEvent(const int id, const long& lparam, const double& dparam, const string& sparam)
{
   // Tidak perlu aksi khusus
}
