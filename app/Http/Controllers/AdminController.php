<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TradingAccount;
use App\Models\TradeHistory;
use App\Models\JournalEntry;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalTrades = TradeHistory::count();
        $totalPnl = TradeHistory::sum('profit_loss');
        $totalAccounts = TradingAccount::count();
        $totalJournals = JournalEntry::count();

        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();
        $recentImports = ImportLog::with('user', 'tradingAccount')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Top users by P&L
        $topUsers = User::withSum('tradeHistories as total_pnl', 'profit_loss')
            ->withCount('tradeHistories')
            ->having('trade_histories_count', '>', 0)
            ->orderByDesc('total_pnl')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers', 'totalTrades', 'totalPnl', 'totalAccounts', 'totalJournals',
            'recentUsers', 'recentImports', 'topUsers'
        ));
    }

    public function users(Request $request)
    {
        $query = User::withCount(['tradeHistories', 'tradingAccounts', 'journalEntries'])
            ->withSum('tradeHistories as total_pnl', 'profit_loss');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('email', 'LIKE', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function updateUserRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        // Admin cannot change their own role
        if ((int) $id === Auth::id()) {
            return back()->with('error', 'Tidak dapat mengubah role sendiri.');
        }

        $user = User::findOrFail($id);
        $user->update(['role' => $request->role]);

        return back()->with('success', "Role {$user->name} berhasil diubah menjadi {$request->role}.");
    }

    public function viewUser($id)
    {
        $user = User::withCount(['tradeHistories', 'tradingAccounts', 'journalEntries'])
            ->withSum('tradeHistories as total_pnl', 'profit_loss')
            ->findOrFail($id);

        $accounts = TradingAccount::where('user_id', $id)
            ->withCount('tradeHistories')
            ->withSum('tradeHistories as total_pnl', 'profit_loss')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentTrades = TradeHistory::where('user_id', $id)
            ->with('tradingAccount')
            ->orderBy('close_date', 'desc')
            ->take(20)
            ->get();

        $totalWins = TradeHistory::where('user_id', $id)->where('result', 'win')->count();
        $totalTrades = TradeHistory::where('user_id', $id)->count();
        $winRate = $totalTrades > 0 ? round(($totalWins / $totalTrades) * 100, 1) : 0;

        $recentJournals = JournalEntry::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.user-detail', compact(
            'user', 'accounts', 'recentTrades', 'recentJournals', 'winRate'
        ));
    }

    public function deleteUser(Request $request, $id)
    {
        // Admin cannot delete themselves
        if ((int) $id === Auth::id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user = User::findOrFail($id);

        // Delete user data (cascade)
        JournalEntry::where('user_id', $id)->delete();
        TradeHistory::where('user_id', $id)->delete();
        TradingAccount::where('user_id', $id)->delete();
        ImportLog::where('user_id', $id)->delete();

        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User {$user->name} berhasil dihapus beserta semua datanya.");
    }
}
