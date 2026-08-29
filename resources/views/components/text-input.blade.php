@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
