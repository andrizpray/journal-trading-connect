<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gray-800/50 hover:bg-gray-700/60 border border-gray-700 hover:border-gray-600 rounded-lg font-semibold text-xs text-gray-200 uppercase tracking-widest backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-40 transition-all duration-200 ease-out']) }}>
    {{ $slot }}
</button>
