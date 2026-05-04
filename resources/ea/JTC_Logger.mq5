//+------------------------------------------------------------------+
//|                                           JTC_Logger.mq5        |
//|                        Journal Trading Connect - EA Logger      |
//|                                     github.com/andrizpray       |
//|  AUDIT v1.3 - Fixed: panel posisi kanan-bawah, WebRequest       |
//|               headers, StringToCharArray buffer, HistorySelect  |
//|               sebelum order loop, MaxTrades guard, response      |
//|               handling 201, Content-Length header               |
//+------------------------------------------------------------------+
#property copyright "Journal Trading Connect"
#property link      "https://github.com/andrizpray/journal-trading-connect"
#property version   "1.30"
#property indicator_chart_window
#property indicator_buffers 0
#property indicator_plots   0

//--- Input parameters
input string InpServerUrl   = "https://eatrade-journal.site"; // Server URL
input string InpApiToken    = "";       // API Token (dari halaman Connect)
input int    InpIntervalSec = 30;       // Kirim data tiap X detik (min 10)
input int    InpMaxTrades   = 50;       // Maks trade per request
input bool   InpDebugMode   = false;    // Print log ke Experts tab

//--- Panel object names
#define PANEL_BG        "JTC_Panel_BG"
#define PANEL_TITLE     "JTC_Panel_Title"
#define PANEL_STATUS    "JTC_Panel_Status"
#define PANEL_DEALS     "JTC_Panel_Deals"
#define PANEL_LASTSYNC  "JTC_Panel_LastSync"
#define PANEL_DOT       "JTC_Panel_Dot"

//--- Panel dimensions (kanan-bawah)
#define PANEL_X      10
#define PANEL_Y      10
#define PANEL_W     270
#define PANEL_H      80

//--- Global variables
datetime g_lastSentTime  = 0;
int      g_lastDealCount = -1;
bool     g_firstRun      = true;
string   g_statusText    = "Initializing...";
color    g_dotColor      = clrGray;
string   g_serverUrl     = "";

//+------------------------------------------------------------------+
//| StripTrailingSlash                                               |
//+------------------------------------------------------------------+
string StripTrailingSlash(string url)
{
   while(StringLen(url) > 0 && StringGetCharacter(url, StringLen(url) - 1) == '/')
      url = StringSubstr(url, 0, StringLen(url) - 1);
   return url;
}

//+------------------------------------------------------------------+
//| OnInit                                                           |
//+------------------------------------------------------------------+
int OnInit()
{
   //--- Validasi API Token
   if(InpApiToken == "")
   {
      Alert("[JTC] ERROR: API Token kosong!\n"
            "1. Login ke web\n"
            "2. Buka Connect > EA Logger\n"
            "3. Copy API Token\n"
            "4. Paste ke parameter InpApiToken");
      Print("[JTC] ERROR: API Token kosong!");
      return INIT_FAILED;
   }

   //--- Validasi dan bersihkan URL
   g_serverUrl = StripTrailingSlash(InpServerUrl);
   if(StringLen(g_serverUrl) < 10)
   {
      Alert("[JTC] ERROR: Server URL tidak valid: " + InpServerUrl);
      return INIT_FAILED;
   }

   //--- Validasi InpMaxTrades
   // FIX: guard nilai tidak valid
   if(InpMaxTrades <= 0)
   {
      Print("[JTC] WARNING: InpMaxTrades harus > 0. Diset ke 50.");
   }

   //--- Interval minimum 10 detik
   int interval = (InpIntervalSec < 10) ? 30 : InpIntervalSec;
   if(InpIntervalSec < 10)
      Print("[JTC] WARNING: Interval terlalu cepat. Diset ke 30 detik.");

   Print("===========================================");
   Print("[JTC] Journal Trading Connect - EA Logger v1.3 (MT5)");
   Print("[JTC] Server  : ", g_serverUrl);
   Print("[JTC] Interval: ", interval, " detik");
   Print("[JTC] MaxTrade: ", InpMaxTrades);
   Print("===========================================");
   Print("[JTC] PENTING: Pastikan URL sudah di-whitelist:");
   Print("[JTC] Tools > Options > Expert Advisors > Allow WebRequest");
   Print("[JTC] Tambah URL: ", g_serverUrl);
   Print("===========================================");

   CreatePanel();
   UpdatePanel("Testing connection...", clrGold);

   //--- Test koneksi saat startup
   if(!TestConnection())
   {
      // Tetap jalan agar user bisa melihat error di panel
      g_firstRun = true;
   }

   EventSetTimer(interval);
   return INIT_SUCCEEDED;
}

//+------------------------------------------------------------------+
//| TestConnection                                                   |
//+------------------------------------------------------------------+
bool TestConnection()
{
   Print("[JTC] Testing connection ke: ", g_serverUrl);
   UpdatePanel("Connecting to server...", clrGold);

   string response = SendRequest("/api/ea/ping", "", "GET");

   if(StringLen(response) == 0)
   {
      Print("[JTC] Connection test GAGAL. Cek:");
      Print("[JTC] 1. Tools > Options > Expert Advisors > Allow WebRequest = ON");
      Print("[JTC] 2. URL whitelist: ", g_serverUrl);
      Print("[JTC] 3. Koneksi internet aktif");
      UpdatePanel("GAGAL: Server unreachable", clrRed);
      SaveLogToFile("startup|FAIL|server_unreachable");
      return false;
   }

   //--- Cek token error dalam response
   if(StringFind(response, "unauthorized") >= 0 ||
      StringFind(response, "invalid_token") >= 0 ||
      StringFind(response, "\"401\"") >= 0)
   {
      Print("[JTC] Token tidak valid! Cek API Token di parameter.");
      UpdatePanel("GAGAL: Token tidak valid!", clrRed);
      SaveLogToFile("startup|FAIL|invalid_token");
      return false;
   }

   Print("[JTC] Connection test OK. Response: ", response);
   UpdatePanel("Connected | Menunggu timer...", clrLimeGreen);
   SaveLogToFile("startup|OK|" + response);
   return true;
}

//+------------------------------------------------------------------+
//| OnDeinit                                                         |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
   EventKillTimer();
   DeletePanel();

   string reasonText = "";
   switch(reason)
   {
      case REASON_PARAMETERS:  reasonText = "Parameter diubah";                           break;
      case REASON_CHARTCHANGE: reasonText = "Chart/Timeframe diubah";                     break;
      case REASON_REMOVE:      reasonText = "Indicator dihapus";                          break;
      case REASON_CLOSE:       reasonText = "Chart ditutup";                              break;
      default:                 reasonText = "Unknown (" + IntegerToString(reason) + ")";  break;
   }
   Print("[JTC] Logger stopped. Reason: ", reasonText);
}

//+------------------------------------------------------------------+
//| OnCalculate                                                      |
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
   return rates_total;
}

//+------------------------------------------------------------------+
//| OnTimer                                                          |
//+------------------------------------------------------------------+
void OnTimer()
{
   HistorySelect(0, TimeCurrent());
   int totalDeals = HistoryDealsTotal();

   if(totalDeals == 0)
   {
      if(InpDebugMode) Print("[JTC] Tidak ada deal di history.");
      UpdatePanel("Menunggu history deal...", clrGold);
      g_firstRun = false;
      return;
   }

   SendTradeHistory(totalDeals);
}

//+------------------------------------------------------------------+
//| GetSLTPFromOrder                                                 |
//| FIX: HistorySelect dipanggil sebelum loop order                 |
//+------------------------------------------------------------------+
void GetSLTPFromOrder(long positionId, double &sl, double &tp)
{
   sl = 0.0;
   tp = 0.0;
   if(positionId <= 0) return;

   // FIX: pastikan history sudah di-select sebelum iterasi order
   HistorySelect(0, TimeCurrent());

   int totalOrders = HistoryOrdersTotal();
   for(int i = 0; i < totalOrders; i++)
   {
      ulong orderTicket = HistoryOrderGetTicket(i);
      if(orderTicket == 0) continue;

      long orderPosId = HistoryOrderGetInteger(orderTicket, ORDER_POSITION_ID);
      if(orderPosId != positionId) continue;

      long orderType = HistoryOrderGetInteger(orderTicket, ORDER_TYPE);
      if(orderType == ORDER_TYPE_BUY || orderType == ORDER_TYPE_SELL)
      {
         sl = HistoryOrderGetDouble(orderTicket, ORDER_SL);
         tp = HistoryOrderGetDouble(orderTicket, ORDER_TP);
         return;
      }
   }
}

//+------------------------------------------------------------------+
//| SendTradeHistory                                                 |
//+------------------------------------------------------------------+
void SendTradeHistory(int totalDeals)
{
   if(totalDeals == g_lastDealCount && !g_firstRun)
   {
      if(InpDebugMode) Print("[JTC] Tidak ada deal baru (", totalDeals, " total).");
      UpdatePanel("Synced | Deals: " + IntegerToString(totalDeals), clrLimeGreen);
      return;
   }

   UpdatePanel("Memproses " + IntegerToString(totalDeals) + " deal...", clrGold);

   // FIX: guard InpMaxTrades <= 0
   int maxTrades = (InpMaxTrades > 0) ? InpMaxTrades : 50;
   int scanStart = MathMax(0, totalDeals - (maxTrades * 3));

   string json      = "{\"trades\":[";
   bool   first     = true;
   int    tradeCount = 0;

   for(int i = totalDeals - 1; i >= scanStart && tradeCount < maxTrades; i--)
   {
      ulong dealTicket = HistoryDealGetTicket(i);
      if(dealTicket == 0) continue;

      long dealEntry = HistoryDealGetInteger(dealTicket, DEAL_ENTRY);
      long dealType  = HistoryDealGetInteger(dealTicket, DEAL_TYPE);

      if(dealEntry != DEAL_ENTRY_OUT && dealEntry != DEAL_ENTRY_INOUT) continue;
      if(dealType  != DEAL_TYPE_BUY  && dealType  != DEAL_TYPE_SELL)  continue;

      double volume = HistoryDealGetDouble(dealTicket, DEAL_VOLUME);
      if(volume <= 0.0) continue;

      double   profit     = HistoryDealGetDouble(dealTicket, DEAL_PROFIT);
      double   swap       = HistoryDealGetDouble(dealTicket, DEAL_SWAP);
      double   comm       = HistoryDealGetDouble(dealTicket, DEAL_COMMISSION);
      string   symbol     = HistoryDealGetString(dealTicket, DEAL_SYMBOL);
      double   closePrice = HistoryDealGetDouble(dealTicket, DEAL_PRICE);
      datetime closeTime  = (datetime)HistoryDealGetInteger(dealTicket, DEAL_TIME);
      long     positionId = HistoryDealGetInteger(dealTicket, DEAL_POSITION_ID);

      double   openPrice  = 0.0;
      datetime openTime   = closeTime;

      //--- Cari deal entry-in yang pasangan
      if(positionId > 0)
      {
         int dealCount = HistoryDealsTotal();
         for(int j = 0; j < dealCount; j++)
         {
            ulong pairedTicket = HistoryDealGetTicket(j);
            if(pairedTicket == 0) continue;

            long pairedPos   = HistoryDealGetInteger(pairedTicket, DEAL_POSITION_ID);
            long pairedEntry = HistoryDealGetInteger(pairedTicket, DEAL_ENTRY);

            if(pairedPos == positionId && pairedEntry == DEAL_ENTRY_IN)
            {
               openPrice = HistoryDealGetDouble(pairedTicket, DEAL_PRICE);
               openTime  = (datetime)HistoryDealGetInteger(pairedTicket, DEAL_TIME);
               break;
            }
         }
      }

      //--- Ambil SL/TP dari history order
      double openSL = 0.0, openTP = 0.0;
      GetSLTPFromOrder(positionId, openSL, openTP);

      int    digits      = (int)SymbolInfoInteger(symbol, SYMBOL_DIGITS);
      if(digits <= 0) digits = 5;

      double totalProfit = profit + swap + comm;
      int    duration    = (openTime > 0 && closeTime > openTime)
                           ? (int)((closeTime - openTime) / 60) : 0;

      // DEAL_TYPE_SELL = posisi BUY yang ditutup (exit sell), sebaliknya
      string tradeType = (dealType == DEAL_TYPE_SELL) ? "buy" : "sell";

      string comment = HistoryDealGetString(dealTicket, DEAL_COMMENT);
      StringReplace(comment, "\"", "'");
      StringReplace(comment, "\\", "/");
      StringReplace(comment, "\n", " ");
      StringReplace(comment, "\r", "");

      if(!first) json += ",";
      first = false;
      tradeCount++;

      json += "{";
      json += "\"ticket\":"           + IntegerToString(dealTicket)                     + ",";
      json += "\"open_date\":\""      + TimeToString(openTime,  TIME_DATE|TIME_MINUTES) + "\",";
      json += "\"close_date\":\""     + TimeToString(closeTime, TIME_DATE|TIME_MINUTES) + "\",";
      json += "\"currency_pair\":\""  + symbol                                          + "\",";
      json += "\"trade_type\":\""     + tradeType                                       + "\",";
      json += "\"lot_size\":"         + DoubleToString(volume, 2)                       + ",";
      json += "\"open_price\":"       + DoubleToString(openPrice,  digits)              + ",";
      json += "\"close_price\":"      + DoubleToString(closePrice, digits)              + ",";
      json += "\"stop_loss\":"        + DoubleToString(openSL, digits)                  + ",";
      json += "\"take_profit\":"      + DoubleToString(openTP, digits)                  + ",";
      json += "\"swap\":"             + DoubleToString(swap, 2)                         + ",";
      json += "\"commission\":"       + DoubleToString(comm, 2)                         + ",";
      json += "\"profit_loss\":"      + DoubleToString(totalProfit, 2)                  + ",";
      json += "\"duration_minutes\":" + IntegerToString(duration)                       + ",";
      json += "\"comment\":\""        + comment                                         + "\"";
      json += "}";
   }

   json += "]}";

   if(tradeCount == 0)
   {
      if(InpDebugMode) Print("[JTC] Tidak ada deal valid untuk dikirim.");
      g_lastDealCount = totalDeals;
      g_firstRun      = false;
      UpdatePanel("Synced | Tidak ada deal valid", clrLimeGreen);
      return;
   }

   UpdatePanel("Mengirim " + IntegerToString(tradeCount) + " trade...", clrDodgerBlue);

   string response = SendRequest("/api/ea/trade/batch", json);

   if(StringLen(response) > 0)
   {
      g_lastDealCount = totalDeals;
      g_lastSentTime  = TimeCurrent();
      g_firstRun      = false;

      string statusMsg = "OK | Sent: " + IntegerToString(tradeCount) + " trades";
      UpdatePanel(statusMsg, clrLimeGreen);
      Print("[JTC] Berhasil kirim ", tradeCount, " trade. Response: ", response);
      SaveLogToFile("sent|" + IntegerToString(tradeCount) + "|" + response);
   }
   else
   {
      UpdatePanel("GAGAL kirim | Cek koneksi/whitelist", clrRed);
      Print("[JTC] GAGAL mengirim! Pastikan:");
      Print("[JTC] 1. Tools > Options > Expert Advisors > Allow WebRequest = ON");
      Print("[JTC] 2. URL sudah ditambahkan: ", g_serverUrl);
      Print("[JTC] 3. API Token benar");
      SaveOfflineTrades(json);
   }
}

//+------------------------------------------------------------------+
//| SendRequest                                                      |
//| FIX: StringToCharArray buffer size, header format dengan         |
//|      Content-Length, semua header diakhiri \r\n                 |
//+------------------------------------------------------------------+
string SendRequest(string endpoint, string jsonBody="", string method="POST")
{
   string url     = g_serverUrl + endpoint;
   int    timeout = 10000;

   // FIX: konversi body ke byte array dengan benar
   uchar dataOut[];
   int   bodyLen = 0;
   if(StringLen(jsonBody) > 0)
   {
      bodyLen = StringToCharArray(jsonBody, dataOut, 0, WHOLE_ARRAY, CP_UTF8) - 1;
      if(bodyLen < 0) bodyLen = 0;
      ArrayResize(dataOut, bodyLen);
   }

   // FIX: setiap header diakhiri \r\n, termasuk header terakhir
   string headers = "Content-Type: application/json\r\n"
                  + "Authorization: Bearer " + InpApiToken + "\r\n"
                  + "Accept: application/json\r\n";
   if(bodyLen > 0)
      headers += "Content-Length: " + IntegerToString(bodyLen) + "\r\n";

   uchar  dataIn[];
   string responseHeaders = "";

   if(InpDebugMode)
   {
      Print("[JTC] ", method, " ", url);
      Print("[JTC] Body length: ", bodyLen, " bytes");
      if(bodyLen <= 500 && bodyLen > 0)
         Print("[JTC] Body: ", jsonBody);
   }

   int res = WebRequest(method, url, headers, timeout, dataOut, dataIn, responseHeaders);

   if(res == -1)
   {
      int    err    = GetLastError();
      string errMsg = "";
      switch(err)
      {
         case 4014: errMsg = "URL tidak bisa di-resolve (DNS error). "
                             "Pastikan URL benar dan internet aktif: " + url;   break;
         case 4060: errMsg = "URL belum di-whitelist! Tambahkan di: "
                             "Tools > Options > Expert Advisors > Allow WebRequest\n"
                             "URL: " + g_serverUrl;                             break;
         case 4024: errMsg = "Internal error WebRequest";                       break;
         case 4073: errMsg = "Timeout (" + IntegerToString(timeout) + "ms). "
                             "Server lambat/tidak merespons";                   break;
         default:   errMsg = "Error code " + IntegerToString(err);             break;
      }
      Print("[JTC] WebRequest gagal: ", errMsg);
      return "";
   }

   string result = CharArrayToString(dataIn, 0, WHOLE_ARRAY, CP_UTF8);

   if(InpDebugMode)
      Print("[JTC] HTTP ", res, " | Response: ", result);

   // FIX: terima 200 dan 201 (Created) sebagai sukses
   if(res != 200 && res != 201)
   {
      Print("[JTC] HTTP Error: ", res, " | Body: ", result);
      return "";
   }

   return result;
}

//+------------------------------------------------------------------+
//| CreatePanel - FIX: posisi CORNER_RIGHT_LOWER                    |
//+------------------------------------------------------------------+
void CreatePanel()
{
   //--- Background
   ObjectCreate(0, PANEL_BG, OBJ_RECTANGLE_LABEL, 0, 0, 0);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_CORNER,    CORNER_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_XDISTANCE, PANEL_X);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_YDISTANCE, PANEL_Y);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_XSIZE,     PANEL_W);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_YSIZE,     PANEL_H);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_BGCOLOR,   C'20,20,30');
   ObjectSetInteger(0, PANEL_BG, OBJPROP_BORDER_TYPE, BORDER_FLAT);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_COLOR,     C'60,60,80');
   ObjectSetInteger(0, PANEL_BG, OBJPROP_WIDTH,     1);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_BACK,      false);
   ObjectSetInteger(0, PANEL_BG, OBJPROP_SELECTABLE, false);

   //--- Title
   ObjectCreate(0, PANEL_TITLE, OBJ_LABEL, 0, 0, 0);
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_CORNER,    CORNER_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_XDISTANCE, PANEL_X + PANEL_W - 8);
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_YDISTANCE, PANEL_Y + PANEL_H - 8);
   ObjectSetString (0, PANEL_TITLE, OBJPROP_TEXT,      "● JTC Logger v1.3");
   ObjectSetString (0, PANEL_TITLE, OBJPROP_FONT,      "Arial Bold");
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_FONTSIZE,  8);
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_COLOR,     C'150,180,255');
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_ANCHOR,    ANCHOR_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_BACK,      false);
   ObjectSetInteger(0, PANEL_TITLE, OBJPROP_SELECTABLE, false);

   //--- Status dot
   ObjectCreate(0, PANEL_DOT, OBJ_LABEL, 0, 0, 0);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_CORNER,    CORNER_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_XDISTANCE, PANEL_X + PANEL_W - 8);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_YDISTANCE, PANEL_Y + PANEL_H - 28);
   ObjectSetString (0, PANEL_DOT, OBJPROP_TEXT,      "■");
   ObjectSetString (0, PANEL_DOT, OBJPROP_FONT,      "Arial Bold");
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_FONTSIZE,  8);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_COLOR,     clrGray);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_ANCHOR,    ANCHOR_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_BACK,      false);
   ObjectSetInteger(0, PANEL_DOT, OBJPROP_SELECTABLE, false);

   //--- Status text
   ObjectCreate(0, PANEL_STATUS, OBJ_LABEL, 0, 0, 0);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_CORNER,    CORNER_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_XDISTANCE, PANEL_X + PANEL_W - 24);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_YDISTANCE, PANEL_Y + PANEL_H - 28);
   ObjectSetString (0, PANEL_STATUS, OBJPROP_TEXT,      "Initializing...");
   ObjectSetString (0, PANEL_STATUS, OBJPROP_FONT,      "Arial");
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_FONTSIZE,  8);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_COLOR,     clrGray);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_ANCHOR,    ANCHOR_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_BACK,      false);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_SELECTABLE, false);

   //--- Last sync label
   ObjectCreate(0, PANEL_LASTSYNC, OBJ_LABEL, 0, 0, 0);
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_CORNER,    CORNER_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_XDISTANCE, PANEL_X + PANEL_W - 8);
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_YDISTANCE, PANEL_Y + PANEL_H - 46);
   ObjectSetString (0, PANEL_LASTSYNC, OBJPROP_TEXT,      "Last sync: -");
   ObjectSetString (0, PANEL_LASTSYNC, OBJPROP_FONT,      "Arial");
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_FONTSIZE,  7);
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_COLOR,     C'120,120,140');
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_ANCHOR,    ANCHOR_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_BACK,      false);
   ObjectSetInteger(0, PANEL_LASTSYNC, OBJPROP_SELECTABLE, false);

   //--- Deals label
   ObjectCreate(0, PANEL_DEALS, OBJ_LABEL, 0, 0, 0);
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_CORNER,    CORNER_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_XDISTANCE, PANEL_X + PANEL_W - 8);
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_YDISTANCE, PANEL_Y + PANEL_H - 60);
   ObjectSetString (0, PANEL_DEALS, OBJPROP_TEXT,      "Deals: -");
   ObjectSetString (0, PANEL_DEALS, OBJPROP_FONT,      "Arial");
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_FONTSIZE,  7);
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_COLOR,     C'120,120,140');
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_ANCHOR,    ANCHOR_RIGHT_LOWER);
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_BACK,      false);
   ObjectSetInteger(0, PANEL_DEALS, OBJPROP_SELECTABLE, false);

   ChartRedraw(0);
}

//+------------------------------------------------------------------+
//| UpdatePanel                                                      |
//+------------------------------------------------------------------+
void UpdatePanel(string statusText, color dotColor)
{
   g_statusText = statusText;
   g_dotColor   = dotColor;

   ObjectSetString (0, PANEL_STATUS, OBJPROP_TEXT,  statusText);
   ObjectSetInteger(0, PANEL_STATUS, OBJPROP_COLOR, dotColor);
   ObjectSetInteger(0, PANEL_DOT,    OBJPROP_COLOR, dotColor);

   string syncStr = (g_lastSentTime > 0)
                    ? TimeToString(g_lastSentTime, TIME_DATE|TIME_MINUTES) : "-";
   ObjectSetString(0, PANEL_LASTSYNC, OBJPROP_TEXT, "Last sync: " + syncStr);

   HistorySelect(0, TimeCurrent());
   int totalDeals = HistoryDealsTotal();
   ObjectSetString(0, PANEL_DEALS, OBJPROP_TEXT, "Deals: " + IntegerToString(totalDeals));

   ChartRedraw(0);
}

//+------------------------------------------------------------------+
//| DeletePanel                                                      |
//+------------------------------------------------------------------+
void DeletePanel()
{
   string objects[] = {PANEL_BG, PANEL_TITLE, PANEL_STATUS,
                       PANEL_DEALS, PANEL_LASTSYNC, PANEL_DOT};
   for(int i = 0; i < ArraySize(objects); i++)
      ObjectDelete(0, objects[i]);
   ChartRedraw(0);
}

//+------------------------------------------------------------------+
//| SaveLogToFile                                                    |
//+------------------------------------------------------------------+
void SaveLogToFile(string text)
{
   int handle = FileOpen("jtc_log.txt",
                         FILE_READ|FILE_WRITE|FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_TXT);
   if(handle == INVALID_HANDLE)
   {
      handle = FileOpen("jtc_log.txt",
                        FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
      if(handle == INVALID_HANDLE) return;
      FileWriteString(handle, "JTC Logger (MT5) v1.3 - Activity Log\r\n");
      FileWriteString(handle, "======================================\r\n");
   }
   FileSeek(handle, 0, SEEK_END);
   FileWriteString(handle,
      TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS) + " | " + text + "\r\n");
   FileClose(handle);
}

//+------------------------------------------------------------------+
//| SaveOfflineTrades                                                |
//+------------------------------------------------------------------+
void SaveOfflineTrades(string jsonBody)
{
   int handle = FileOpen("jtc_offline_buffer.txt",
                         FILE_READ|FILE_WRITE|FILE_SHARE_READ|FILE_SHARE_WRITE|FILE_TXT);
   if(handle == INVALID_HANDLE)
   {
      handle = FileOpen("jtc_offline_buffer.txt",
                        FILE_WRITE|FILE_SHARE_READ|FILE_TXT);
      if(handle == INVALID_HANDLE) return;
      FileWriteString(handle, "# JTC Offline Buffer (MT5) v1.3\r\n");
   }
   FileSeek(handle, 0, SEEK_END);
   FileWriteString(handle,
      TimeToString(TimeCurrent(), TIME_DATE|TIME_SECONDS) + "|" + jsonBody + "\r\n");
   FileClose(handle);
}

//+------------------------------------------------------------------+
//| OnChartEvent                                                     |
//+------------------------------------------------------------------+
void OnChartEvent(const int id, const long &lparam,
                  const double &dparam, const string &sparam)
{
   // reserved
}