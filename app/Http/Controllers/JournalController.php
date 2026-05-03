<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::where('user_id', Auth::id())
            ->with('tradeHistory.tradingAccount');

        // Filter by result
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        // 3.3 — Filter by tag
        if ($request->filled('tag')) {
            $query->where('tags', 'LIKE', '%' . $request->tag . '%');
        }

        // 3.6 — Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('analysis', 'LIKE', '%' . $search . '%')
                  ->orWhere('lesson_learned', 'LIKE', '%' . $search . '%')
                  ->orWhere('currency_pair', 'LIKE', '%' . $search . '%')
                  ->orWhere('strategy_used', 'LIKE', '%' . $search . '%')
                  ->orWhere('tags', 'LIKE', '%' . $search . '%')
                  ->orWhere('plan_reasoning', 'LIKE', '%' . $search . '%');
            });
        }

        // Filter by template type
        if ($request->filled('template')) {
            $query->where('template_type', $request->template);
        }

        $entries = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Get all unique tags for filter dropdown
        $allTags = JournalEntry::where('user_id', Auth::id())
            ->whereNotNull('tags')
            ->where('tags', '!=', '')
            ->pluck('tags')
            ->flatMap(fn($t) => array_map('trim', explode(',', $t)))
            ->unique()
            ->sort()
            ->values();

        return view('journal.index', compact('entries', 'allTags'));
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
            // 3.3 — Tags
            'tags' => 'nullable|string|max:500',
            // 3.4 — Template type
            'template_type' => 'nullable|string|max:50',
            // 3.4 — Trading plan
            'plan_setup' => 'nullable|string|max:255',
            'plan_entry' => 'nullable|string|max:255',
            'plan_sl' => 'nullable|numeric',
            'plan_tp' => 'nullable|numeric',
            'plan_reasoning' => 'nullable|string',
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
            'tags' => 'nullable|string|max:500',
            'template_type' => 'nullable|string|max:50',
            'plan_setup' => 'nullable|string|max:255',
            'plan_entry' => 'nullable|string|max:255',
            'plan_sl' => 'nullable|numeric',
            'plan_tp' => 'nullable|numeric',
            'plan_reasoning' => 'nullable|string',
        ]);

        $entry->update($validated);

        return redirect()->route('journal.index')
            ->with('success', 'Jurnal berhasil diperbarui!');
    }

    // 3.2 — Upload screenshot
    public function uploadScreenshot(Request $request, $id)
    {
        $entry = JournalEntry::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'screenshot' => 'required|image|max:5120',
        ]);

        if ($entry->screenshot_path) {
            Storage::disk('public')->delete($entry->screenshot_path);
        }

        $path = $request->file('screenshot')->store('screenshots', 'public');
        $entry->update(['screenshot_path' => $path]);

        // Return JSON for AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => 'Screenshot berhasil diupload!']);
        }

        return redirect()->back()->with('success', 'Screenshot berhasil diupload!');
    }

    // 3.2 — Delete screenshot
    public function deleteScreenshot($id)
    {
        $entry = JournalEntry::where('user_id', Auth::id())->findOrFail($id);

        if ($entry->screenshot_path) {
            Storage::disk('public')->delete($entry->screenshot_path);
            $entry->update(['screenshot_path' => null]);
        }

        return redirect()->back()
            ->with('success', 'Screenshot berhasil dihapus!');
    }

    public function destroy($id)
    {
        $entry = JournalEntry::where('user_id', Auth::id())->findOrFail($id);

        // Delete screenshot file
        if ($entry->screenshot_path) {
            Storage::disk('public')->delete($entry->screenshot_path);
        }

        $entry->delete();

        return redirect()->route('journal.index')
            ->with('success', 'Jurnal berhasil dihapus!');
    }

    // 3.5 — Weekly/Monthly review
    public function review(Request $request)
    {
        $period = $request->filled('period') ? $request->period : 'weekly';

        $now = now();
        if ($period === 'monthly') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $prevStart = $now->copy()->subMonth()->startOfMonth();
            $prevEnd = $now->copy()->subMonth()->endOfMonth();
        } else {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
            $prevStart = $now->copy()->subWeek()->startOfWeek();
            $prevEnd = $now->copy()->subWeek()->endOfWeek();
        }

        // Current period trades
        $currentTrades = TradeHistory::where('user_id', Auth::id())
            ->whereBetween('close_date', [$start, $end]);

        $totalTrades = (clone $currentTrades)->count();
        $wins = (clone $currentTrades)->where('result', 'win')->count();
        $losses = (clone $currentTrades)->where('result', 'loss')->count();
        $winRate = $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 1) : 0;
        $totalPnl = (clone $currentTrades)->sum('profit_loss');
        $avgPnl = $totalTrades > 0 ? round($totalPnl / $totalTrades, 2) : 0;

        // Top pair
        $topPairs = (clone $currentTrades)
            ->selectRaw('currency_pair, COUNT(*) as trades, SUM(profit_loss) as pnl')
            ->groupBy('currency_pair')
            ->orderByDesc('pnl')
            ->take(5)
            ->get();

        // Current period journals
        $journals = JournalEntry::where('user_id', Auth::id())
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get();

        // Top lessons
        $topLessons = $journals->pluck('lesson_learned')
            ->filter()
            ->take(5)
            ->values();

        // Emotion trend
        $emotionScores = $journals->pluck('emotion_score')->filter();
        $avgEmotion = $emotionScores->isNotEmpty() ? round($emotionScores->avg(), 1) : null;

        // Previous period for comparison
        $prevPnl = TradeHistory::where('user_id', Auth::id())
            ->whereBetween('close_date', [$prevStart, $prevEnd])
            ->sum('profit_loss');

        $pnlChange = $prevPnl != 0 ? round((($totalPnl - $prevPnl) / abs($prevPnl)) * 100, 1) : null;

        $periodLabel = $period === 'monthly'
            ? $now->format('F Y')
            . ' (1–' . $now->format('d') . ')'
            : $start->format('d M') . ' – ' . $end->format('d M Y');

        return view('journal.review', compact(
            'period', 'periodLabel',
            'totalTrades', 'wins', 'losses', 'winRate',
            'totalPnl', 'avgPnl', 'topPairs', 'topLessons',
            'avgEmotion', 'pnlChange', 'prevPnl',
        ));
    }
}
