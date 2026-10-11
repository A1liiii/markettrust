@extends('layouts.app')

@section('title', 'Ubah Profil - MarketTrust')

@section('content')
<div class="min-h-[calc(100vh-16rem)] py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
    <div class="glass-panel p-6 sm:p-10 relative overflow-hidden">
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-white/60">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Pengaturan Profil & Keamanan
            </h1>
            <p class="text-sm text-slate-600 mt-1">Perbarui foto profil avatar dan kelola kata sandi akun MarketTrust Anda.</p>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Seksi 1: Avatar -->
            <div class="space-y-4">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Foto Avatar Profil
                </h3>

                <div class="flex flex-col sm:flex-row items-center gap-5">
                    <div class="shrink-0">
                        @if ($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-20 h-20 rounded-2xl object-cover ring-2 ring-white/80 shadow-md">
                        @else
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center text-2xl font-bold shadow-md">
                                {{ strtoupper(substr($user->username, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 w-full">
                        <label for="avatar" class="glass-label">Pilih File Avatar Baru</label>
                        <input 
                            type="file" 
                            id="avatar" 
                            name="avatar" 
                            accept="image/png,image/jpeg,image/webp"
                            class="glass-input w-full px-3 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <p class="text-[11px] text-slate-500 mt-1">Format: JPG, JPEG, PNG, atau WEBP. Maksimal 2MB.</p>
                    </div>
                </div>
            </div>

            <!-- Seksi 2: Informasi Akun -->
            <div class="space-y-4 pt-4 border-t border-slate-200/60">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                    </svg>
                    Informasi Akun & Username
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="glass-label">Username Publik <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-sm">@</span>
                            <input 
                                type="text" 
                                id="username"
                                name="username" 
                                value="{{ old('username', $user->username) }}" 
                                required
                                pattern="[a-z0-9_]{3,30}"
                                class="glass-input w-full pl-8 pr-3.5 py-2.5 text-sm font-medium @error('username') border-rose-400 focus:border-rose-500 @enderror"
                                placeholder="username_anda"
                            >
                        </div>
                        @error('username')
                            <p class="text-[11px] text-rose-600 font-medium mt-1">{{ $message }}</p>
                        @else
                            <p class="text-[10px] text-slate-500 mt-1">3–30 karakter: huruf kecil [a-z], angka [0-9], dan garis bawah [_].</p>
                        @enderror
                    </div>
                    <div>
                        <label class="glass-label">Alamat Email</label>
                        <input 
                            type="email" 
                            value="{{ $user->email }}" 
                            disabled 
                            class="glass-input w-full px-3.5 py-2.5 text-sm bg-slate-100/60 text-slate-500 cursor-not-allowed"
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Email akun terverifikasi (tidak dapat diubah mandiri).</p>
                    </div>
                </div>
            </div>

            <!-- Seksi 3: Keamanan & Password -->
            <div class="space-y-4 pt-4 border-t border-slate-200/60">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Kata Sandi (Password)
                </h3>

                @if (! $user->hasPassword())
                    <div class="glass-alert glass-alert-info">
                        <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-xs text-indigo-900 leading-relaxed">
                            <strong>Akun Google Terdaftar:</strong> Anda login menggunakan Google dan belum memiliki kata sandi lokal. Anda dapat membuat kata sandi di bawah ini untuk mengizinkan login melalui form email & password.
                        </div>
                    </div>
                @else
                    <div>
                        <label for="current_password" class="glass-label">Password Saat Ini (Lama)</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            placeholder="Masukkan password saat ini jika ingin mengganti"
                            class="glass-input w-full px-3.5 py-2.5 text-sm"
                        >
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="glass-label">
                            {{ $user->hasPassword() ? 'Password Baru' : 'Buat Password Baru' }}
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Minimal 8 karakter"
                            class="glass-input w-full px-3.5 py-2.5 text-sm"
                        >
                    </div>
                    <div>
                        <label for="password_confirmation" class="glass-label">Konfirmasi Password Baru</label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            placeholder="Ulangi password baru"
                            class="glass-input w-full px-3.5 py-2.5 text-sm"
                        >
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">Kosongkan kolom password jika Anda hanya ingin memperbarui avatar profil.</p>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200/60">
                <a href="{{ route('profile.show') }}" class="glass-btn-secondary py-2.5 px-5 text-sm">
                    Batal
                </a>
                <button type="submit" class="glass-btn-primary py-2.5 px-6 text-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
