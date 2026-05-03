<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::where('user_id', Auth::id())
            ->with('tradeHistory.tradingAccount');

        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        $entries = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('journal.index', compact('entries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency_pair' => 'required|string|max:20',
            'trade_type' => 'required|in:buy,sell',
            'profit_loss' => 'required|numeric',
            'result' => 'required|in:win,loss,break_even',
            'analysis' => 'nullable|string',
            'lesson_learned' => 'nullable|string',
            'emotion_score' => 'nullable|integer|min:1|max:5',
            'market_condition' => 'nullable|string|max:50',
            'strategy_used' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();

        JournalEntry::create($validated);

        return redirect()->route('journal.index')
            ->with('success', 'Jurnal berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $entry = JournalEntry::where('user_id', Auth::id())->findOrFail($id);
        return view('journal.edit', compact('entry'));
    }

    public function update(Request $request, $id)
    {
        $entry = JournalEntry::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'currency_pair' => 'required|string|max:20',
            'trade_type' => 'required|in:buy,sell',
            'profit_loss' => 'required|numeric',
            'result' => 'required|in:win,loss,break_even',
            'analysis' => 'nullable|string',
            'lesson_learned' => 'nullable|string',
            'emotion_score' => 'nullable|integer|min:1|max:5',
            'market_condition' => 'nullable|string|max:50',
            'strategy_used' => 'nullable|string|max:255',
        ]);

        $entry->update($validated);

        return redirect()->route('journal.index')
            ->with('success', 'Jurnal berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $entry = JournalEntry::where('user_id', Auth::id())->findOrFail($id);
        $entry->delete();

        return redirect()->route('journal.index')
            ->with('success', 'Jurnal berhasil dihapus!');
    }
}
