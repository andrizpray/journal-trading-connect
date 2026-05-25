@extends('layouts.app')

@section('title', 'Kelola User')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white">Kelola User</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1">{{ $users->total() }} user terdaftar</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-white text-sm flex items-center gap-2 transition-colors">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    {{-- Search & Filter --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                class="dark-input w-full px-4 py-2.5 rounded-lg text-sm">
        </div>
        <select name="role" class="dark-input px-4 py-2.5 rounded-lg text-sm min-w-[140px]">
            <option value="">Semua Role</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
        </select>
        <button type="submit" class="btn-primary px-4 py-2.5 rounded-lg text-sm">
            <i class="fas fa-search"></i>
        </button>
        @if(request('search') || request('role'))
            <a href="{{ route('admin.users') }}" class="text-gray-400 hover:text-white text-sm flex items-center gap-1 px-3 py-2.5">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif
    </form>

    {{-- Users Table --}}
    <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs sm:text-sm">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-900/30">
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3 text-center">Role</th>
                        <th class="px-4 py-3 text-center">Akun</th>
                        <th class="px-4 py-3 text-center">Trades</th>
                        <th class="px-4 py-3 text-center">P&L</th>
                        <th class="px-4 py-3 text-center">Journal</th>
                        <th class="px-4 py-3">Bergabung</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/30">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-700/20 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.user-detail', $user->id) }}" class="flex items-center gap-3 group">
                                    <div class="w-8 h-8 rounded-full bg-cyan-900/50 flex items-center justify-center text-cyan-400 text-sm font-bold flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-white group-hover:text-cyan-400 transition-colors">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($user->id === auth()->id())
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-900/50 text-amber-400">Admin (Anda)</span>
                                @else
                                    <form method="POST" action="{{ route('admin.update-role', $user->id) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" class="text-[10px] px-2 py-1 rounded-full border-0 cursor-pointer
                                            {{ $user->role === 'admin' ? 'bg-amber-900/50 text-amber-400' : 'bg-gray-700/50 text-gray-300' }}
                                            focus:ring-1 focus:ring-cyan-500">
                                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-300">{{ $user->trading_accounts_count }}</td>
                            <td class="px-4 py-3 text-center text-gray-300">{{ number_format($user->trade_histories_count) }}</td>
                            <td class="px-4 py-3 text-center font-medium {{ $user->total_pnl >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                {{ $user->total_pnl >= 0 ? '+' : '' }}{{ number_format($user->total_pnl ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-300">{{ $user->journal_entries_count }}</td>
                            <td class="px-4 py-3 text-gray-400 whitespace-nowrap">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($user->id !== auth()->id())
                                    <button onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')"
                                        class="text-red-400 hover:text-red-300 transition-colors p-1">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                @else
                                    <span class="text-gray-600 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Tidak ada user ditemukan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-700/30">
                {{ $users->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 max-w-sm mx-4 w-full">
        <h3 class="text-lg font-bold text-white mb-2">Hapus User?</h3>
        <p class="text-sm text-gray-400 mb-5">
            User <span id="deleteUserName" class="text-white font-medium"></span> dan semua data terkait (trade, journal, akun trading) akan dihapus permanen.
        </p>
        <form id="deleteForm" method="POST" class="flex gap-3 justify-end">
            @csrf
            @method('DELETE')
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('flex')"
                class="px-4 py-2 rounded-lg text-sm text-gray-300 hover:bg-gray-700 transition-colors">
                Batal
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-red-600 hover:bg-red-700 text-white transition-colors">
                Hapus
            </button>
        </form>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = '{{ route('admin.delete-user', 0) }}'.replace('/0', '/' + userId);
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}
</script>
@endsection
