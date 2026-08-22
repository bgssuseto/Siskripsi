<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-950/60 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-slate-100 tracking-tight">Lupa Password?</h2>
    </div>

    <div class="p-5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-2xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path>
            </svg>
            <p class="text-sm text-amber-800 dark:text-amber-300 leading-relaxed">
                Untuk keamanan akun, reset password tidak dapat dilakukan secara mandiri di sini.
                Silakan menghubungi <strong>Komite Skripsi Program Studi Teknik Informatika</strong>
                untuk dibantu mengatur ulang password akun Anda.
            </p>
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke halaman login
        </a>
    </div>
</x-guest-layout>
