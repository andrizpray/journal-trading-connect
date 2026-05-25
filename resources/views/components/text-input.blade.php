@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-gray-900 border border-gray-700 text-gray-100 placeholder-gray-500 rounded-lg px-3.5 py-2.5 shadow-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 focus:outline-none transition-colors duration-200']) }}>
