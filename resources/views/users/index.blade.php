<x-app-layout title="Manajemen User">
    <x-slot:header>
        Manajemen User & Role
    </x-slot:header>

    <div x-data="{ 
        createModal: false, 
        editModal: false,
        deleteModal: false,
        selectedUser: { id: null, name: '', email: '', role: 'mahasiswa', dosen_id: null },
        deleteUrl: '',
        errors: {},
        isLoading: false,
        
        openEdit(user) {
            this.selectedUser = { ...user };
            this.errors = {};
            this.editModal = true;
        },
        openDelete(id, name) {
            this.selectedUser.id = id;
            this.selectedUser.name = name;
            this.deleteUrl = '{{ url('users') }}/' + id;
            this.deleteModal = true;
        },
        async navigate(url) {
            window.history.pushState(null, '', url);
            await refreshComponent(['#stats-container', '#table-container', '#filter-container']);
        },
        submitSearch(form) {
            const url = new URL(form.action || window.location.href);
            const formData = new FormData(form);
            url.searchParams.delete('search');
            url.searchParams.delete('page');
            for (const [key, value] of formData.entries()) {
                if (value) {
                    url.searchParams.set(key, value);
                }
            }
            const currentUrl = new URL(window.location.href);
            if (currentUrl.searchParams.has('role')) {
                url.searchParams.set('role', currentUrl.searchParams.get('role'));
            }
            this.navigate(url.toString());
        },
        async submitCreate(e) {
            this.isLoading = true;
            this.errors = {};
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok) {
                    this.createModal = false;
                    form.reset();
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#stats-container', '#table-container']);
                } else {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                    }
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitEdit(e) {
            this.isLoading = true;
            this.errors = {};
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok) {
                    this.editModal = false;
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#stats-container', '#table-container']);
                } else {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                        if (result.message && !result.errors) {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'error' } }));
                        }
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Terjadi kesalahan.', type: 'error' } }));
                    }
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitDelete(e) {
            this.isLoading = true;
            const form = e.target;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new FormData(form)
                });
                const result = await response.json();
                if (response.ok) {
                    this.deleteModal = false;
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message, type: 'success' } }));
                    await refreshComponent(['#stats-container', '#table-container']);
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menghapus user.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        }
    }">

        <!-- Page Header & Action -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen User</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola data pengguna, hak akses, dan role dalam sistem.</p>
            </div>
            <button @click="createModal = true" 
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold text-sm shadow-md shadow-indigo-600/20 hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Tambah User Baru
            </button>
        </div>

        <!-- Summary Stat Cards -->
        <div id="stats-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
            <!-- Total -->
            <a href="{{ route('users.index') }}" @click.prevent="navigate($event.currentTarget.href)" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total User</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center group-hover:bg-slate-900 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Super Admin -->
            <a href="{{ route('users.index', ['role' => 'super_admin']) }}" @click.prevent="navigate($event.currentTarget.href)" class="bg-white p-5 rounded-2xl border border-purple-100 shadow-sm hover:shadow-md transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-600">Super Admin</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['super_admin'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Koordinator -->
            <a href="{{ route('users.index', ['role' => 'koordinator']) }}" @click.prevent="navigate($event.currentTarget.href)" class="bg-white p-5 rounded-2xl border border-blue-100 shadow-sm hover:shadow-md transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Koordinator</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['koordinator'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Dosen -->
            <a href="{{ route('users.index', ['role' => 'dosen']) }}" @click.prevent="navigate($event.currentTarget.href)" class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm hover:shadow-md transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Dosen</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['dosen'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Mahasiswa -->
            <a href="{{ route('users.index', ['role' => 'mahasiswa']) }}" @click.prevent="navigate($event.currentTarget.href)" class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm hover:shadow-md transition-all group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Mahasiswa</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['mahasiswa'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Filter & Search Bar -->
        <div id="filter-container" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 mb-6">
            <form method="GET" action="{{ route('users.index') }}" @submit.prevent="submitSearch($event.currentTarget)" class="flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Role Tabs -->
                <div class="flex items-center p-1 bg-slate-100 rounded-xl w-full sm:w-auto overflow-x-auto">
                    <a href="{{ route('users.index', array_filter(['search' => request('search')])) }}" 
                       @click.prevent="navigate($event.currentTarget.href)"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ !request('role') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        Semua Role
                    </a>
                    <a href="{{ route('users.index', array_merge(request()->except('page'), ['role' => 'super_admin'])) }}" 
                       @click.prevent="navigate($event.currentTarget.href)"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ request('role') === 'super_admin' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        Super Admin
                    </a>
                    <a href="{{ route('users.index', array_merge(request()->except('page'), ['role' => 'koordinator'])) }}" 
                       @click.prevent="navigate($event.currentTarget.href)"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ request('role') === 'koordinator' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        Koordinator
                    </a>
                    <a href="{{ route('users.index', array_merge(request()->except('page'), ['role' => 'dosen'])) }}" 
                       @click.prevent="navigate($event.currentTarget.href)"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ request('role') === 'dosen' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        Dosen
                    </a>
                    <a href="{{ route('users.index', array_merge(request()->except('page'), ['role' => 'mahasiswa'])) }}" 
                       @click.prevent="navigate($event.currentTarget.href)"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all whitespace-nowrap {{ request('role') === 'mahasiswa' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                        Mahasiswa
                    </a>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           @input.debounce.500ms="submitSearch($event.target.form)"
                           placeholder="Cari nama atau email..." 
                           class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white transition-all font-medium">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div id="table-container" @click="if ($event.target.closest('a')) { const link = $event.target.closest('a'); if (link.href && !link.hasAttribute('download') && !link.getAttribute('href').startsWith('#') && link.target !== '_blank') { $event.preventDefault(); navigate(link.href); } }" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-6">User</th>
                            <th class="py-3.5 px-6">Role</th>
                            <th class="py-3.5 px-6">Tanggal Bergabung</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-500 font-medium">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $user->role_badge_class }}">
                                    {{ $user->role_label }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium">
                                {{ $user->created_at ? $user->created_at->translatedFormat('d M Y, H:i') : '-' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit Button -->
                                    <button @click="openEdit({{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'dosen_id' => $user->dosen_id]) }})" 
                                            class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit User">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    @if(Auth::id() !== $user->id)
                                    <button @click="openDelete({{ $user->id }}, '{{ addslashes($user->name) }}')" 
                                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus User">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-500">
                                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                </div>
                                <p class="font-bold text-slate-700">Tidak ada data user ditemukan</p>
                                <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci atau filter yang berbeda.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
            @endif
        </div>

        <!-- MODAL: Tambah User Baru -->
        <div x-show="createModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" 
             style="display: none;"
             x-transition>
            <div @click.away="createModal = false" class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-2xl">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900">Tambah User Baru</h3>
                    <button @click="createModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('users.store') }}" method="POST" @submit.prevent="submitCreate($event)" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200">
                        <template x-if="errors.name">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Alamat Email</label>
                        <input type="email" name="email" required placeholder="budi@skripsi.ac.id" class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200">
                        <template x-if="errors.email">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.email[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Role User</label>
                        <select name="role" x-model="selectedUser.role" required class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200 cursor-pointer">
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="dosen">Dosen</option>
                            <option value="koordinator">Koordinator</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <template x-if="errors.role">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.role[0]"></p>
                        </template>
                    </div>
                    <!-- Link Dosen dropdown (only shown if role is Dosen) -->
                    <div x-show="selectedUser.role === 'dosen'">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Hubungkan ke Profil Dosen</label>
                        <select name="dosen_id" class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200 cursor-pointer">
                            <option value="">-- Pilih Profil Dosen (Opsional) --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}">{{ $d->nidn }} - {{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                        <template x-if="errors.dosen_id">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.dosen_id[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Password</label>
                        <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter" class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200">
                        <template x-if="errors.password">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.password[0]"></p>
                        </template>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" :disabled="isLoading" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 disabled:opacity-50">
                            <span x-show="!isLoading">Simpan User</span>
                            <span x-show="isLoading">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Edit User -->
        <div x-show="editModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" 
             style="display: none;"
             x-transition>
            <div @click.away="editModal = false" class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-2xl">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900">Edit User</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form :action="'{{ url('users') }}/' + selectedUser.id" method="POST" @submit.prevent="submitEdit($event)" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="selectedUser.name" required class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200">
                        <template x-if="errors.name">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Alamat Email</label>
                        <input type="email" name="email" x-model="selectedUser.email" required class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200">
                        <template x-if="errors.email">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.email[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Role User</label>
                        <select name="role" x-model="selectedUser.role" required class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200 cursor-pointer">
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="dosen">Dosen</option>
                            <option value="koordinator">Koordinator</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <template x-if="errors.role">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.role[0]"></p>
                        </template>
                    </div>
                    <!-- Link Dosen dropdown (only shown if role is Dosen) -->
                    <div x-show="selectedUser.role === 'dosen'">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Hubungkan ke Profil Dosen</label>
                        <select name="dosen_id" x-model="selectedUser.dosen_id" class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200 cursor-pointer">
                            <option value="">-- Pilih Profil Dosen (Opsional) --</option>
                            @foreach($dosens as $d)
                                <option value="{{ $d->id }}">{{ $d->nidn }} - {{ $d->nama_dosen }}</option>
                            @endforeach
                        </select>
                        <template x-if="errors.dosen_id">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.dosen_id[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Password Baru (Opsional)</label>
                        <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" minlength="8" class="w-full text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50/50 focus:bg-white px-4 py-2.5 transition-all duration-200">
                        <template x-if="errors.password">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.password[0]"></p>
                        </template>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" :disabled="isLoading" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 disabled:opacity-50">
                            <span x-show="!isLoading">Update User</span>
                            <span x-show="isLoading">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Hapus User Confirmation -->
        <div x-show="deleteModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" 
             style="display: none;"
             x-transition>
            <div @click.away="deleteModal = false" class="bg-white rounded-2xl border border-slate-200 max-w-sm w-full p-6 shadow-2xl text-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Hapus User?</h3>
                <p class="text-xs text-slate-500 mt-2">Apakah Anda yakin ingin menghapus user <span class="font-bold text-slate-800" x-text="selectedUser.name"></span>? Tindakan ini tidak dapat dibatalkan.</p>

                <form :action="deleteUrl" method="POST" @submit.prevent="submitDelete($event)" class="mt-6 flex items-center justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit" :disabled="isLoading" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 disabled:opacity-50">
                        <span x-show="!isLoading">Ya, Hapus</span>
                        <span x-show="isLoading">Menghapus...</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
