<?php

namespace App\Exports;

use App\Models\TradeHistory;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TradeHistoryExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private ?int $accountId = null,
        private ?string $pair = null,
        private ?string $result = null,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
    ) {}

    public function query()
    {
        $query = TradeHistory::where('user_id', Auth::id())
            ->with('tradingAccount');

        if ($this->accountId) {
            $query->where('trading_account_id', $this->accountId);
        }
        if ($this->pair) {
            $query->where('currency_pair', $this->pair);
        }
        if ($this->result) {
            $query->where('result', $this->result);
        }
        if ($this->dateFrom) {
            $query->where('close_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('close_date', '<=', $this->dateTo . ' 23:59:59');
        }

        return $query->orderBy('close_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Ticket',
            'Tanggal Buka',
            'Tanggal Tutup',
            'Pair',
            'Tipe',
            'Lot',
            'Harga Buka',
            'Harga Tutup',
            'Stop Loss',
            'Take Profit',
            'Swap',
            'Commission',
            'Profit/Loss',
            'Hasil',
            'Durasi (menit)',
            'Akun',
            'Broker',
            'Comment',
        ];
    }

    public function map($trade): array
    {
        return [
            $trade->ticket,
            $trade->open_date?->format('Y-m-d H:i:s'),
            $trade->close_date?->format('Y-m-d H:i:s'),
            $trade->currency_pair,
            strtoupper($trade->trade_type),
            $trade->lot_size,
            $trade->open_price,
            $trade->close_price,
            $trade->stop_loss,
            $trade->take_profit,
            $trade->swap,
            $trade->commission,
            $trade->profit_loss,
            strtoupper($trade->result),
            $trade->duration_minutes,
            $trade->tradingAccount?->account_number,
            $trade->tradingAccount?->broker,
            $trade->comment,
        ];
    }
}
