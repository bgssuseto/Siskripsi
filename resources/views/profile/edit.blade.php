<x-app-layout title="Kelola Profil Saya">
<div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
    
    <!-- Ringkasan Profil -->
    <section class="bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700/80 rounded-3xl shadow-sm p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <div class="relative shrink-0 self-start sm:self-auto">
                @if($user->foto_profil)
                    <img src="{{ asset($user->foto_profil) }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                @else
                    <div class="w-20 h-20 rounded-2xl bg-indigo-50 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-100 dark:ring-indigo-500/20 font-extrabold flex items-center justify-center text-3xl">
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900" title="Akun aktif"></span>
            </div>

            <div class="min-w-0 flex-1">
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100 break-words">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1.5 min-w-0">
                    <x-icon name="mail" class="w-4 h-4 shrink-0" /> <span class="truncate">{{ $user->email }}</span>
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $user->role_badge_class }}">{{ $user->role_label }}</span>
                    @if($user->nim)
                        <span class="ui-chip">NIM: <strong class="font-mono text-slate-900 dark:text-white">{{ $user->nim }}</strong></span>
                    @endif
                    <span class="ui-chip"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Akun aktif</span>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base"><x-icon name="check-circle" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></span>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 font-bold"><x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></button>
        </div>
    @endif

    <!-- 2 Columns Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column (2 Cols): Personal Details & Avatar Upload -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-7 shadow-sm space-y-6">
                
                <div class="pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900">
                        <x-icon name="user" class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Informasi Pribadi & Kontak</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Perbarui informasi profil, pasfoto, dan nomor kontak Anda.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <!-- Custom Styled Avatar File Input -->
                    <div x-data="{
                        fileName: '',
                        previewUrl: '{{ $user->foto_profil ? asset($user->foto_profil) : '' }}',
                        handleFile(e) {
                            const file = e.target.files[0];
                            if (file) {
                                this.fileName = file.name;
                                this.previewUrl = URL.createObjectURL(file);
                            }
                        }
                    }">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Foto Profil / Avatar
                        </label>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-4 p-4 bg-slate-50/80 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700">
                            <!-- Thumbnail Preview Box -->
                            <div class="relative shrink-0">
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" class="w-16 h-16 rounded-2xl object-cover border border-slate-300 dark:border-slate-600 shadow-xs">
                                </template>
                                <template x-if="!previewUrl">
                                    <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 font-bold flex items-center justify-center text-xl border border-indigo-200 dark:border-indigo-800">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    </div>
                                </template>
                            </div>

                            <!-- Upload Button Trigger -->
                            <div class="space-y-1.5 text-center sm:text-left flex-1">
                                <label for="foto_profil" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-extrabold text-xs rounded-xl border border-slate-300 dark:border-slate-600 shadow-2xs cursor-pointer transition-colors">
                                    <span><x-icon name="folder" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Pilih Foto Baru</span>
                                </label>
                                <input type="file" id="foto_profil" name="foto_profil" @change="handleFile($event)" accept="image/*" class="sr-only">
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium" x-text="fileName ? 'File terpilih: ' + fileName : 'Format: JPG, PNG, WEBP (Maks 2MB)'"></p>
                            </div>
                        </div>
                        @error('foto_profil')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Input Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        @error('name')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Input Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Alamat Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        @error('email')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Input NIM (Khusus Mahasiswa) / NIDN (Khusus Dosen) -->
                    @if(Auth::user()->hasRole('mahasiswa'))
                    <div>
                        <label for="nim" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Nomor Induk Mahasiswa (NIM)
                        </label>
                        <input type="text" id="nim" name="nim" value="{{ old('nim', $user->nim) }}" placeholder="Contoh: 202251xxx"
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold font-mono bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        @error('nim')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                    @elseif(Auth::user()->hasRole('dosen'))
                    <div>
                        <label for="nidn" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Nomor Induk Dosen Nasional (NIDN)
                        </label>
                        <input type="text" id="nidn" name="nidn" value="{{ old('nidn', $user->dosen->nidn ?? '') }}" placeholder="Contoh: 0012345678"
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold font-mono bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        @error('nidn')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif

                    <!-- Input No HP -->
                    <div>
                        <label for="no_hp" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Nomor WhatsApp / HP
                        </label>
                        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 08123456789"
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        @error('no_hp')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="submit" 
                                style="background-color: #4361ee; color: #ffffff;"
                                class="px-6 py-2.5 text-white font-extrabold text-xs rounded-2xl transition-all shadow-md border cursor-pointer" style="background:#4361ee; border-color:#3251d4;">
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column (1 Col): Security & Change Password -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 sm:p-7 shadow-sm space-y-6">
                
                <div class="pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 font-bold flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900">
                        <x-icon name="lock" class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 dark:text-slate-100">Perbarui Kata Sandi</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pastikan akun Anda aman dengan kata sandi yang kuat.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Kata Sandi Saat Ini
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 transition-all">
                        @error('current_password', 'updatePassword')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Kata Sandi Baru
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 transition-all">
                        @error('password', 'updatePassword')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-500/20 transition-all">
                        @error('password_confirmation', 'updatePassword')
                            <p class="text-rose-600 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button type="submit" 
                                style="background-color: #d97706; color: #ffffff;"
                                class="w-full py-2.5 bg-amber-600 text-white font-extrabold text-xs rounded-2xl transition-all shadow-md hover:bg-amber-700 border border-amber-500 cursor-pointer text-center">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
</x-app-layout>
