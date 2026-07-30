<x-app-layout title="Manajemen Menu">
    <x-slot:header>
        Manajemen Menu & Hak Akses User
    </x-slot:header>

    <div x-data="{ 
        activeTab: 'all',
        search: '',
        createMenuModal: false,
        editMenuModal: false,
        userAccessModal: false,
        selectedUser: { id: null, name: '', email: '', role: '' },
        userMenuIds: [],
        roleAccessModal: false,
        selectedRole: '',
        roleMenuIds: [],
        editMenu: { id: null, name: '', route: '', icon: '', role_default: 'all', sort_order: 0, is_active: true },
        errors: {},
        isLoading: false,
        
        openEditMenu(menu) {
            this.editMenu = { ...menu };
            this.errors = {};
            this.editMenuModal = true;
        },
        openUserAccessModal(user, menuIds) {
            this.selectedUser = user;
            this.userMenuIds = menuIds;
            this.userAccessModal = true;
        },
        openRoleAccessModal(role, menuIds) {
            this.selectedRole = role;
            this.roleMenuIds = menuIds;
            this.roleAccessModal = true;
        },
        async submitCreateMenu(e) {
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
                    this.createMenuModal = false;
                    form.reset();
                    window.location.reload();
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
        async submitEditMenu(e) {
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
                    this.editMenuModal = false;
                    window.location.reload();
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
        async submitDeleteMenu(form) {
            if (!confirm('Yakin ingin menghapus menu ini?')) return;
            this.isLoading = true;
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
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menghapus menu.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitAssignUserMenus(e) {
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
                    this.userAccessModal = false;
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menyimpan hak akses.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
        async submitAssignRoleMenus(e) {
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
                    this.roleAccessModal = false;
                    window.location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: result.message || 'Gagal menyimpan hak akses role.', type: 'error' } }));
                }
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Terjadi kesalahan jaringan.', type: 'error' } }));
            } finally {
                this.isLoading = false;
            }
        },
    }">

        <!-- Page Header & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Menu</h2>
                <p class="text-sm text-slate-500 mt-1">Kelola hak akses menu dinamis per-user dan struktur menu sistem.</p>
            </div>
            @php
                $dosenMenuIds = \Illuminate\Support\Facades\DB::table('role_menu')->where('role', 'dosen')->pluck('menu_id')->toArray();
            @endphp
            <div class="flex gap-2">
                <button @click="openRoleAccessModal('dosen', {{ json_encode($dosenMenuIds) }})" 
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold text-sm shadow-md shadow-orange-500/20 hover:from-amber-600 hover:to-orange-700 transition-all duration-200 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Atur Akses Role Dosen
                </button>
                <button @click="createMenuModal = true" 
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold text-sm shadow-md shadow-indigo-600/20 hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Menu Baru
                </button>
            </div>
        </div>

        <!-- Summary Stat Cards (5 Grid Cards) -->
        <div id="stats-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
            <!-- Total Menu -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Menu</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $menus->count() }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Super Admin Default -->
            <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-600">Super Admin</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $users->where('role', 'super_admin')->count() }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Koordinator -->
            <div class="bg-white p-5 rounded-2xl border border-blue-100 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Koordinator</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $users->where('role', 'koordinator')->count() }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Dosen -->
            <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Dosen</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $users->where('role', 'dosen')->count() }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Mahasiswa -->
            <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Mahasiswa</p>
                        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $users->where('role', 'mahasiswa')->count() }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs & Search Bar Card -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Role Tabs Filter -->
            <div class="flex items-center gap-1 p-1 bg-slate-100/80 dark:bg-slate-800/80 rounded-xl overflow-x-auto max-w-full whitespace-nowrap scrollbar-none">
                <button @click="activeTab = 'all'" 
                        :class="activeTab === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0">
                    Semua Role
                </button>
                <button @click="activeTab = 'super_admin'" 
                        :class="activeTab === 'super_admin' ? 'bg-white dark:bg-slate-700 text-purple-700 dark:text-purple-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0">
                    Super Admin
                </button>
                <button @click="activeTab = 'koordinator'" 
                        :class="activeTab === 'koordinator' ? 'bg-white dark:bg-slate-700 text-blue-700 dark:text-blue-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0">
                    Koordinator
                </button>
                <button @click="activeTab = 'dosen'" 
                        :class="activeTab === 'dosen' ? 'bg-white dark:bg-slate-700 text-amber-700 dark:text-amber-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0">
                    Dosen
                </button>
                <button @click="activeTab = 'mahasiswa'" 
                        :class="activeTab === 'mahasiswa' ? 'bg-white dark:bg-slate-700 text-emerald-700 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all shrink-0">
                    Mahasiswa
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative w-full md:w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" x-model="search" placeholder="Cari nama atau email..." 
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all dark:text-slate-200">
            </div>
        </div>

        <div id="tables-container">
        <!-- TABLE 1: User Hak Akses Management Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900 text-base">Hak Akses Menu User Dinamis</h3>
                <span class="text-xs text-slate-400">Klik 'Atur Akses' untuk mengelola hak akses menu per-user dalam bentuk pop-up</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-slate-400 text-[11px] font-bold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-4 px-6 w-72">USER</th>
                            <th class="py-4 px-6 w-40">ROLE</th>
                            <th class="py-4 px-6">HAK AKSES MENU</th>
                            <th class="py-4 px-6 text-center w-36">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        @foreach($users as $u)
                            @php
                                $userAccessibleMenus = $u->getAccessibleMenus();
                                $menuCount = $userAccessibleMenus->count();
                                $userMenuIds = $u->menus->pluck('id')->toArray();
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors"
                                x-show="(activeTab === 'all' || '{{ $u->role }}' === activeTab) && (!search || '{{ strtolower(addslashes($u->name . ' ' . $u->email)) }}'.includes(search.toLowerCase()))">
                                <!-- USER -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-sm shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 leading-snug">{{ $u->name }}</p>
                                            <p class="text-xs text-slate-400 font-mono">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- ROLE -->
                                <td class="py-4 px-6">
                                    @if($u->isSuperAdmin())
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700 inline-block">
                                            Super Admin
                                        </span>
                                    @elseif($u->isKoordinator())
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 inline-block">
                                            Koordinator
                                        </span>
                                    @elseif($u->isDosen())
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 inline-block">
                                            Dosen
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 inline-block">
                                            Mahasiswa
                                        </span>
                                    @endif
                                </td>

                                <!-- HAK AKSES MENU -->
                                <td class="py-4 px-6">
                                    @if($u->isSuperAdmin())
                                        <span class="px-3 py-1 bg-purple-50 border border-purple-200/80 text-purple-700 text-xs font-bold rounded-lg inline-block">
                                            Semua Menu Sistem ({{ $menuCount }})
                                        </span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5 items-center">
                                            @foreach($userAccessibleMenus->take(3) as $am)
                                                <span class="px-2.5 py-1 bg-slate-100 border border-slate-200/80 text-slate-700 text-xs font-medium rounded-lg">
                                                    {{ $am->name }}
                                                </span>
                                            @endforeach
                                            @if($menuCount > 3)
                                                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-md border border-indigo-100">
                                                    +{{ $menuCount - 3 }} menu lagi
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- AKSI (Opens Pop Up Modal) -->
                                <td class="py-4 px-6 text-center">
                                    <button type="button" 
                                            @click="openUserAccessModal({ id: {{ $u->id }}, name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', role: '{{ $u->role }}' }, {{ json_encode($userMenuIds) }})"
                                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition-all shadow-sm">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Atur Akses
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABLE 2: System Menus Master Table (with explicit top margin mt-8) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mt-8">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-extrabold text-slate-900 text-base">Daftar Master Menu Sistem</h3>
                <span class="text-xs text-slate-400">Total {{ $menus->count() }} menu terdaftar</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 text-slate-400 text-[11px] font-bold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-4 px-6 w-12 text-center">#</th>
                            <th class="py-4 px-6">NAMA MENU</th>
                            <th class="py-4 px-6">ROUTE NAME</th>
                            <th class="py-4 px-6">ROLE DEFAULT</th>
                            <th class="py-4 px-6 text-center">STATUS</th>
                            <th class="py-4 px-6 text-right w-28">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium">
                        @foreach($menus as $i => $m)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-6 text-center text-slate-400 text-xs font-semibold">{{ $m->sort_order ?? ($i + 1) }}</td>
                            <td class="py-4 px-6 font-bold text-slate-900">{{ $m->name }}</td>
                            <td class="py-4 px-6 font-mono text-xs text-indigo-600 dark:text-indigo-400 font-bold">{{ $m->route ?? '-' }}</td>
                            <td class="py-4 px-6 text-xs">
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold capitalize">
                                    {{ $m->role_default ?? 'all' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($m->is_active)
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Aktif</span>
                                @else
                                    <span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">Nonaktif</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Edit Button -->
                                    <button type="button" 
                                        @click="openEditMenu({ id: {{ $m->id }}, name: '{{ addslashes($m->name) }}', route: '{{ addslashes($m->route ?? '') }}', icon: '{{ addslashes($m->icon ?? '') }}', role_default: '{{ $m->role_default ?? 'all' }}', sort_order: {{ $m->sort_order ?? 0 }}, is_active: {{ $m->is_active ? 'true' : 'false' }} })"
                                        class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all" title="Edit Menu">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ route('admin.menus.destroy', $m) }}" @submit.prevent="submitDeleteMenu($event.currentTarget)" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all" title="Hapus Menu">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <!-- MODAL 1: ATUR HAK AKSES USER (POP UP MODAL) -->
        <div x-show="userAccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 my-8 border border-slate-100" @click.away="userAccessModal = false">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <template x-if="selectedUser && selectedUser.id">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white font-extrabold flex items-center justify-center text-base shadow-md shadow-indigo-600/20"
                                 x-text="selectedUser.name.charAt(0).toUpperCase()"></div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-lg leading-tight" x-text="`Pengaturan Hak Akses: ${selectedUser.name}`"></h3>
                                <p class="text-xs text-slate-400 font-medium mt-0.5" x-text="`${selectedUser.email} • Role: ${selectedUser.role.toUpperCase()}`"></p>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="userAccessModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-all font-bold">&times;</button>
                </div>

                <!-- Modal Body Form -->
                <template x-if="selectedUser && selectedUser.id">
                    <form :action="`{{ url('/kelola-menu/user') }}/${selectedUser.id}`" method="POST" @submit.prevent="submitAssignUserMenus($event)" class="space-y-6">
                        @csrf
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">Pilih Menu Sistem yang Diberikan untuk User Ini:</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[380px] overflow-y-auto pr-1.5">
                                @foreach($menus as $menu)
                                    @php
                                        $isSempro = in_array($menu->route, ['mahasiswa.pendaftaran.index', 'mahasiswa.sempro.index'], true);
                                    @endphp

                                    <!-- Locked Mandatory for Mahasiswa Sempro -->
                                    <template x-if="selectedUser.role === 'mahasiswa' && {{ $isSempro ? 'true' : 'false' }}">
                                        <div class="px-4 py-3.5 rounded-2xl border border-indigo-200 bg-indigo-50/70 flex items-center justify-between gap-3 shadow-xs">
                                            <div class="flex items-center gap-3.5 min-w-0">
                                                <div class="w-5 h-5 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <input type="hidden" name="menu_ids[]" value="{{ $menu->id }}">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-xs sm:text-sm text-indigo-950 truncate">{{ $menu->name }}</p>
                                                    <p class="text-[11px] font-mono text-indigo-500 dark:text-indigo-300 font-bold truncate mt-0.5">{{ $menu->route ?? 'Tanpa Route' }}</p>
                                                </div>
                                            </div>
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold bg-indigo-600 text-white rounded-lg uppercase tracking-wider shrink-0">WAJIB</span>
                                        </div>
                                    </template>

                                    <!-- Dynamic Checkbox for Other Menus -->
                                    <template x-if="!(selectedUser.role === 'mahasiswa' && {{ $isSempro ? 'true' : 'false' }})">
                                        <label for="modal_menu_{{ $menu->id }}" 
                                               class="px-4 py-3.5 rounded-2xl border transition-all flex items-center justify-between gap-3 cursor-pointer select-none"
                                               :class="(userMenuIds.includes({{ $menu->id }}) || '{{ $menu->role_default }}' === 'all' || '{{ $menu->role_default }}' === selectedUser.role) ? 'bg-indigo-50/50 border-indigo-300 shadow-xs ring-1 ring-indigo-200/60' : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'">
                                            
                                            <div class="flex items-center gap-3.5 min-w-0">
                                                <input type="checkbox" name="menu_ids[]" value="{{ $menu->id }}" id="modal_menu_{{ $menu->id }}"
                                                    :checked="userMenuIds.includes({{ $menu->id }}) || '{{ $menu->role_default }}' === 'all' || '{{ $menu->role_default }}' === selectedUser.role"
                                                    @change="if($el.checked) { if(!userMenuIds.includes({{ $menu->id }})) userMenuIds.push({{ $menu->id }}) } else { userMenuIds = userMenuIds.filter(id => id !== {{ $menu->id }}) }"
                                                    class="w-5 h-5 rounded-lg border-2 border-slate-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer shrink-0 transition-all">
                                                
                                                <div class="min-w-0">
                                                    <p class="font-bold text-xs sm:text-sm text-slate-900 truncate leading-snug">{{ $menu->name }}</p>
                                                    <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400 font-bold truncate mt-0.5">{{ $menu->route ?? 'Tanpa Route' }}</p>
                                                </div>
                                            </div>

                                            <div class="shrink-0">
                                                <template x-if="'{{ $menu->role_default }}' === 'all' || '{{ $menu->role_default }}' === selectedUser.role">
                                                    <span class="px-2.5 py-1 text-[10px] font-extrabold bg-blue-100/80 border border-blue-200 text-blue-700 rounded-lg">Default Role</span>
                                                </template>
                                                <template x-if="userMenuIds.includes({{ $menu->id }}) && !('{{ $menu->role_default }}' === 'all' || '{{ $menu->role_default }}' === selectedUser.role)">
                                                    <span class="px-2.5 py-1 text-[10px] font-extrabold bg-emerald-100/80 border border-emerald-200 text-emerald-700 rounded-lg">Kustom User</span>
                                                </template>
                                            </div>
                                        </label>
                                    </template>
                                @endforeach
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="userAccessModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-all">Batal</button>
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Hak Akses
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL 2: TAMBAH MENU BARU -->
        <div x-show="createMenuModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-8 space-y-5 border border-slate-100" @click.away="createMenuModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-lg">Tambah Menu Sistem Baru</h3>
                    <button type="button" @click="createMenuModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-all font-bold">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.menus.store') }}" @submit.prevent="submitCreateMenu($event)" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Menu</label>
                        <input type="text" name="name" required placeholder="Contoh: Jadwal Ujian" 
                               class="w-full text-sm px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                        <template x-if="errors.name">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Route Name (opsional)</label>
                        <input type="text" name="route" placeholder="Contoh: sidang.index" 
                               class="w-full text-sm font-mono px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role Default Access</label>
                        <select name="role_default" class="w-full text-sm px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                            <option value="all">Semua Role (All)</option>
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="koordinator">Koordinator</option>
                            <option value="dosen">Dosen</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Urutan (Sort Order)</label>
                        <input type="number" name="sort_order" value="0" 
                               class="w-full text-sm px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="createMenuModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-all">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">Simpan Menu</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: EDIT MENU SISTEM -->
        <div x-show="editMenuModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-8 space-y-5 border border-slate-100" @click.away="editMenuModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-extrabold text-slate-900 text-lg">Edit Menu Sistem</h3>
                    <button type="button" @click="editMenuModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-all font-bold">&times;</button>
                </div>

                <form :action="`{{ route('admin.menus.index') }}/${editMenu.id}`" method="POST" @submit.prevent="submitEditMenu($event)" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Menu</label>
                        <input type="text" name="name" x-model="editMenu.name" required 
                               class="w-full text-sm px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                        <template x-if="errors.name">
                            <p class="text-xs text-rose-600 mt-1 font-semibold" x-text="errors.name[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Route Name</label>
                        <input type="text" name="route" x-model="editMenu.route" 
                               class="w-full text-sm font-mono px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role Default Access</label>
                        <select name="role_default" x-model="editMenu.role_default" 
                                class="w-full text-sm px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                            <option value="all">Semua Role (All)</option>
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="koordinator">Koordinator</option>
                            <option value="dosen">Dosen</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Urutan</label>
                        <input type="number" name="sort_order" x-model="editMenu.sort_order" 
                               class="w-full text-sm px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" id="edit_is_active" :checked="editMenu.is_active" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                        <label for="edit_is_active" class="text-sm font-bold text-slate-700 cursor-pointer">Menu Aktif</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="editMenuModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-all">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 4: ATUR HAK AKSES ROLE -->
        <div x-show="roleAccessModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 my-8 border border-slate-100" @click.away="roleAccessModal = false">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white font-extrabold flex items-center justify-center text-base shadow-md shadow-amber-500/20">R</div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-lg leading-tight" x-text="`Pengaturan Hak Akses Role: Dosen`"></h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Konfigurasi menu default yang dapat diakses oleh seluruh pengguna dengan Role Dosen.</p>
                        </div>
                    </div>
                    <button type="button" @click="roleAccessModal = false" class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-all font-bold">&times;</button>
                </div>

                <!-- Modal Body Form -->
                <form :action="`{{ url('/kelola-menu/role') }}/${selectedRole}`" method="POST" @submit.prevent="submitAssignRoleMenus($event)" class="space-y-6">
                    @csrf
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">Pilih Menu Sistem yang Aktif untuk Role Ini:</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[380px] overflow-y-auto pr-1.5">
                            @foreach($menus as $menu)
                                <label for="role_menu_{{ $menu->id }}" 
                                       class="px-4 py-3.5 rounded-2xl border transition-all flex items-center justify-between gap-3 cursor-pointer select-none"
                                       :class="roleMenuIds.includes({{ $menu->id }}) ? 'bg-indigo-50/50 border-indigo-300 shadow-xs ring-1 ring-indigo-200/60' : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'">
                                    
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <input type="checkbox" name="menu_ids[]" value="{{ $menu->id }}" id="role_menu_{{ $menu->id }}"
                                            :checked="roleMenuIds.includes({{ $menu->id }})"
                                            @change="if($el.checked) { if(!roleMenuIds.includes({{ $menu->id }})) roleMenuIds.push({{ $menu->id }}) } else { roleMenuIds = roleMenuIds.filter(id => id !== {{ $menu->id }}) }"
                                            class="w-5 h-5 rounded-lg border-2 border-slate-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer shrink-0 transition-all">
                                        
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs sm:text-sm text-slate-900 truncate leading-snug">{{ $menu->name }}</p>
                                            <p class="text-[11px] font-mono text-slate-400 truncate mt-0.5">{{ $menu->route ?? 'Tanpa Route' }}</p>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        <template x-if="roleMenuIds.includes({{ $menu->id }})">
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold bg-amber-100 text-amber-700 border border-amber-200 rounded-lg">Aktif</span>
                                        </template>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="roleAccessModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-all">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan Hak Akses
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
