<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin Tabungan Anggota</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f7] text-[#171717] antialiased">
        <header class="border-b border-[#d8d8d8] bg-white">
            <div class="mx-auto flex h-20 max-w-[1440px] items-center justify-between px-6 lg:px-10">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-4" aria-label="Beranda admin">
                    <img src="{{ asset('images/koperasi-logo.png') }}" alt="Logo koperasi" class="h-16 w-16 object-contain">
                    <span class="text-xl font-semibold">Admin Tabungan</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full border border-[#063c55] px-5 py-2 text-sm font-semibold text-[#063c55] transition hover:bg-[#063c55] hover:text-white" type="submit">Keluar</button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-6 py-5 lg:px-28">
            <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-center">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-1 gap-3">
                    <label class="flex h-12 flex-1 items-center rounded-full border border-[#c8c8c8] bg-white px-5 shadow-sm">
                        <span class="mr-3 text-xl text-[#a5a5a5]" aria-hidden="true">⌕</span>
                        <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau nomor anggota" class="w-full border-0 bg-transparent text-lg outline-none placeholder:text-[#777]">
                    </label>
                    <button class="h-12 rounded-full bg-[#063c55] px-10 text-lg font-semibold text-white transition hover:bg-[#07516f]" type="submit">Cari</button>
                </form>
                <form method="GET" action="{{ route('admin.dashboard') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <select name="status" onchange="this.form.submit()" aria-label="Filter status anggota" class="h-12 rounded-full border border-[#c8c8c8] bg-white px-6 text-lg font-semibold shadow-sm outline-none">
                        <option value="ACTIVE" @selected($selectedStatus === 'ACTIVE')>Aktif</option>
                        <option value="INACTIVE" @selected($selectedStatus === 'INACTIVE')>Tidak aktif</option>
                        <option value="SUSPENDED" @selected($selectedStatus === 'SUSPENDED')>Ditangguhkan</option>
                        <option value="" @selected($selectedStatus === '')>Semua anggota</option>
                    </select>
                </form>
                <div class="flex h-12 items-center justify-center gap-3 rounded-full border border-[#c8c8c8] bg-white px-6 text-lg font-semibold shadow-sm">
                    <span>Jumlah Anggota: {{ $totalMembers }}</span><span class="text-2xl" aria-hidden="true">♧</span>
                </div>
            </div>

            <div class="mb-4 flex items-center justify-between text-sm text-[#666]">
                <span><span id="active-member-count">{{ $activeMembers }}</span> anggota aktif</span>
                <span>{{ $members->total() }} hasil ditampilkan</span>
            </div>

            <section id="member-grid" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Daftar anggota">
                @forelse ($members as $member)
                    @php($latest = $member->latestSavingsTransaction)
                    @php($isInactive = $member->status !== 'ACTIVE')
                    <article class="member-card relative min-h-[205px] rounded-xl border border-[#e0e0e0] bg-white p-4 shadow-[0_2px_5px_rgba(0,0,0,0.12)]" data-member-id="{{ $member->id }}" data-member-code="{{ $member->member_code ?: '—' }}" data-member-status="{{ $member->status }}">
                        <span class="member-status absolute right-0 top-0 rounded-bl-lg rounded-tr-xl px-3 py-1 text-sm font-semibold text-white {{ $isInactive ? 'bg-[#777]' : 'bg-[#063c55]' }}">
                            {{ $isInactive ? ($member->status === 'SUSPENDED' ? 'Ditangguhkan' : 'Tidak aktif') : 'Aktif' }}
                        </span>
                        <div class="pr-24">
                            <h2 class="truncate text-xl font-semibold">{{ $member->name }}</h2>
                            <p class="mt-2 text-xs font-medium uppercase tracking-wide text-[#777]">{{ $member->member_code ?: 'Tanpa nomor anggota' }} · {{ $member->occupation ?: 'Pekerjaan belum diisi' }}</p>
                            <p class="mt-1 truncate text-sm text-[#555]">{{ $member->address ?: 'Alamat belum diisi' }}</p>
                        </div>
                        <p class="mt-4 text-sm font-medium text-[#333]">Saldo: <span class="member-balance">Rp {{ number_format((float) ($latest?->balance_after ?? 0), 2, ',', '.') }}</span></p>
                        <p class="member-latest mt-1 text-xs text-[#777]">
                            @if ($latest)
                                {{ $latest->type === 'DEPOSIT' ? 'Setoran' : 'Penarikan' }} terakhir Rp {{ number_format((float) $latest->amount, 2, ',', '.') }} · {{ $latest->created_at?->format('d/m/Y H:i') }}
                            @else
                                Belum ada transaksi tabungan
                            @endif
                        </p>
                        <div class="mt-3 flex gap-3">
                            <button type="button" data-action="open" data-type="DEPOSIT" @disabled($isInactive) title="Setoran hanya dapat dicatat untuk anggota aktif" class="flex-1 rounded-lg bg-[#063c55] py-2 text-base font-medium text-white transition hover:bg-[#07516f] disabled:cursor-not-allowed disabled:opacity-40">Setor</button>
                            <button type="button" data-action="open" data-type="WITHDRAWAL" class="flex-1 rounded-lg border border-[#063c55] py-2 text-base font-medium text-[#063c55] transition hover:bg-[#edf7fa]">Tarik</button>
                            <button type="button" data-action="history" class="rounded-lg border border-[#c8c8c8] px-4 py-2 text-base text-[#333] transition hover:bg-[#f2f2f2]">Detail</button>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl bg-white p-8 text-center text-[#666]">Tidak ada anggota yang cocok.</p>
                @endforelse
            </section>

            <div class="mt-6">{{ $members->links() }}</div>
            <p class="mt-4 text-center text-xs text-[#777]">Saldo dan transaksi diperbarui otomatis setiap 5 detik.</p>
        </main>

        <div id="savings-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/45 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-member-name">
            <section class="my-8 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-[#e1e5e7] p-5 sm:p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#66757d]">Transaksi tabungan</p>
                        <h2 id="modal-member-name" class="mt-1 text-xl font-semibold text-[#063c55]">Anggota</h2>
                        <p id="modal-member-code" class="mt-1 text-sm text-[#66757d]"></p>
                    </div>
                    <button type="button" data-close-modal class="rounded-full px-3 py-1 text-2xl leading-none text-[#66757d] hover:bg-[#f2f5f6]" aria-label="Tutup">×</button>
                </header>
                <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <div>
                        <div class="rounded-xl bg-[#eff7fa] p-4">
                            <p class="text-sm text-[#52636b]">Saldo saat ini</p>
                            <p id="modal-balance" class="mt-1 text-2xl font-bold text-[#063c55]">Rp 0,00</p>
                        </div>
                        <form id="savings-form" class="mt-5 space-y-4">
                            <label class="block text-sm font-medium">Jenis transaksi
                                <select id="transaction-type" name="type" class="mt-1 h-11 w-full rounded-lg border border-[#c8d2d7] bg-white px-3">
                                    <option value="DEPOSIT">Setoran</option>
                                    <option value="WITHDRAWAL">Penarikan</option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium">Jenis tabungan
                                <select id="transaction-category" name="category" required class="mt-1 h-11 w-full rounded-lg border border-[#c8d2d7] bg-white px-3">
                                    <option value="">Pilih jenis tabungan</option>
                                    <option value="POKOK">Tabungan Pokok</option>
                                    <option value="WAJIB">Tabungan Wajib</option>
                                    <option value="SUKARELA">Tabungan Sukarela</option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium">Nominal (Rp)
                                <input id="transaction-amount" name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" inputmode="decimal" required class="mt-1 h-11 w-full rounded-lg border border-[#c8d2d7] px-3" placeholder="Contoh: 50000">
                            </label>
                            <label class="block text-sm font-medium">Catatan <span class="font-normal text-[#777]">(opsional)</span>
                                <textarea id="transaction-note" name="note" rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border border-[#c8d2d7] px-3 py-2" placeholder="Catatan transaksi"></textarea>
                            </label>
                            <p id="form-message" class="hidden rounded-lg bg-[#fff2f0] px-3 py-2 text-sm text-[#a0271d]" role="alert"></p>
                            <button id="submit-transaction" type="submit" class="w-full rounded-lg bg-[#063c55] px-4 py-3 font-semibold text-white transition hover:bg-[#07516f]">Simpan transaksi</button>
                        </form>
                    </div>
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-semibold text-[#173c4c]">Riwayat transaksi</h3>
                            <span id="history-page-label" class="text-xs text-[#66757d]"></span>
                        </div>
                        <div class="mb-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                            <label class="block text-sm font-medium text-[#52636b]">Tampilkan
                                <select id="history-filter" class="mt-1 h-10 w-full rounded-lg border border-[#c8d2d7] bg-white px-3 text-sm text-[#171717]">
                                    <option value="HISTORY">Riwayat</option>
                                    <option value="POKOK">Pokok</option>
                                    <option value="WAJIB">Wajib</option>
                                    <option value="SUKARELA">Sukarela</option>
                                </select>
                            </label>
                            <div class="rounded-lg bg-[#f4f7f8] px-3 py-2">
                                <p id="category-total-label" class="text-xs text-[#66757d]">Saldo keseluruhan</p>
                                <p id="category-total-value" class="mt-1 font-semibold text-[#063c55]">Rp 0,00</p>
                            </div>
                        </div>
                        <p id="uncategorized-note" class="mb-3 hidden rounded-lg bg-[#fff8df] px-3 py-2 text-xs text-[#704f00]"></p>
                        <div class="overflow-x-auto rounded-xl border border-[#e1e5e7]">
                            <table class="w-full min-w-[420px] text-left text-sm">
                                <thead class="bg-[#f4f7f8] text-xs uppercase tracking-wide text-[#66757d]"><tr><th class="px-3 py-2">Tanggal</th><th class="px-3 py-2">Transaksi</th><th class="px-3 py-2 text-right">Nominal</th><th class="px-3 py-2 text-right">Saldo</th></tr></thead>
                                <tbody id="transaction-rows"><tr><td colspan="4" class="px-3 py-5 text-center text-[#777]">Memuat riwayat…</td></tr></tbody>
                            </table>
                        </div>
                        <div class="mt-3 flex justify-between">
                            <button id="history-previous" type="button" class="rounded border border-[#c8d2d7] px-3 py-1.5 text-sm text-[#063c55] disabled:opacity-40">Sebelumnya</button>
                            <button id="history-next" type="button" class="rounded border border-[#c8d2d7] px-3 py-1.5 text-sm text-[#063c55] disabled:opacity-40">Berikutnya</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <script>
            (() => {
                const modal = document.getElementById('savings-modal');
                const form = document.getElementById('savings-form');
                const grid = document.getElementById('member-grid');
                const apiBase = @json(url('/admin/savings'));
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                let currentMemberId = null;
                let currentPage = 1;
                let lastPage = 1;
                let refreshing = false;
                let pendingTransaction = null;

                const formatMoney = (value) => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));
                const categoryNames = { POKOK: 'Pokok', WAJIB: 'Wajib', SUKARELA: 'Sukarela' };
                const uuid = () => window.crypto?.randomUUID ? window.crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                    const r = Math.random() * 16 | 0;
                    return (c === 'x' ? r : (r & 3 | 8)).toString(16);
                });
                const memberCard = (id) => grid.querySelector(`[data-member-id="${id}"]`);

                function updateCard(member) {
                    const card = memberCard(member.id);
                    if (!card) return;
                    card.querySelector('.member-balance').textContent = `Rp ${formatMoney(member.balance)}`;
                    const badge = card.querySelector('.member-status');
                    const active = member.status === 'ACTIVE';
                    badge.textContent = active ? 'Aktif' : (member.status === 'SUSPENDED' ? 'Ditangguhkan' : 'Tidak aktif');
                    badge.classList.toggle('bg-[#777]', !active);
                    badge.classList.toggle('bg-[#063c55]', active);
                    const latest = card.querySelector('.member-latest');
                    if (!member.latest_transaction) {
                        latest.textContent = 'Belum ada transaksi tabungan';
                    } else {
                        const transaction = member.latest_transaction;
                        const kind = transaction.type === 'DEPOSIT' ? 'Setoran' : 'Penarikan';
                        const date = new Date(transaction.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
                        latest.textContent = `${kind} terakhir Rp ${formatMoney(transaction.amount)} · ${date}`;
                    }
                }

                async function refreshSnapshot() {
                    if (refreshing || document.hidden) return;
                    const ids = [...grid.querySelectorAll('[data-member-id]')].map((card) => card.dataset.memberId);
                    if (ids.length === 0) return;
                    refreshing = true;
                    try {
                        const query = new URLSearchParams();
                        ids.forEach((id) => query.append('ids[]', id));
                        const response = await fetch(`${@json(route('admin.savings.snapshot'))}?${query}`, { headers: { Accept: 'application/json' } });
                        if (!response.ok) return;
                        const payload = await response.json();
                        document.getElementById('active-member-count').textContent = payload.active_members;
                        payload.members.forEach(updateCard);
                        if (currentMemberId) await loadMember(currentPage, false);
                    } catch (error) {
                        console.error('Gagal memperbarui saldo tabungan.', error);
                    } finally {
                        refreshing = false;
                    }
                }

                async function loadMember(page = 1, showLoading = true) {
                    if (!currentMemberId) return;
                    currentPage = page;
                    const rows = document.getElementById('transaction-rows');
                    const categoryFilter = document.getElementById('history-filter').value;
                    if (showLoading) rows.innerHTML = '<tr><td colspan="4" class="px-3 py-5 text-center text-[#777]">Memuat riwayat…</td></tr>';
                    try {
                        const response = await fetch(`${apiBase}/${currentMemberId}?page=${page}&category=${categoryFilter}`, { headers: { Accept: 'application/json' } });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(payload.message || 'Riwayat transaksi gagal dimuat.');
                        document.getElementById('modal-member-name').textContent = payload.customer.name;
                        document.getElementById('modal-member-code').textContent = `Anggota ${payload.customer.member_code || '—'} · ${payload.customer.status}`;
                        document.getElementById('modal-balance').textContent = `Rp ${formatMoney(payload.balance)}`;
                        document.getElementById('history-filter').value = payload.category_filter;
                        document.getElementById('category-total-label').textContent = categoryFilter === 'HISTORY'
                            ? 'Saldo keseluruhan'
                            : `Total tabungan ${categoryNames[categoryFilter]}`;
                        document.getElementById('category-total-value').textContent = `Rp ${formatMoney(categoryFilter === 'HISTORY' ? payload.balance : payload.category_totals[categoryFilter])}`;
                        const unclassifiedNote = document.getElementById('uncategorized-note');
                        unclassifiedNote.textContent = payload.uncategorized_transactions > 0
                            ? `${payload.uncategorized_transactions} transaksi lama belum memiliki kategori. Transaksi tersebut tetap masuk saldo keseluruhan dan Riwayat.`
                            : '';
                        unclassifiedNote.classList.toggle('hidden', payload.uncategorized_transactions === 0);
                        const items = payload.transactions.data;
                        rows.replaceChildren();
                        if (items.length === 0) {
                            const empty = document.createElement('tr');
                            const cell = document.createElement('td');
                            cell.colSpan = 5;
                            cell.className = 'px-3 py-5 text-center text-[#777]';
                            cell.textContent = 'Belum ada transaksi.';
                            empty.append(cell);
                            rows.append(empty);
                        } else {
                            items.forEach((transaction) => {
                                const row = document.createElement('tr');
                                row.className = 'border-t border-[#e8edef]';
                                const date = new Date(transaction.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
                                const transactionType = transaction.type === 'DEPOSIT' ? 'Setoran' : 'Penarikan';
                                const categoryName = categoryNames[transaction.category] || 'Belum dikategorikan';
                                [date, `${transactionType} · ${categoryName}`, `Rp ${formatMoney(transaction.amount)}`, `Rp ${formatMoney(transaction.balance_after)}`].forEach((value, index) => {
                                    const cell = document.createElement('td');
                                    cell.className = `px-3 py-2 ${index > 1 ? 'text-right' : ''}`;
                                    cell.textContent = value;
                                    row.append(cell);
                                });
                                row.title = `${transaction.reference_code} · ${transaction.recorded_by?.name || 'Admin'}${transaction.note ? ` · ${transaction.note}` : ''}`;
                                rows.append(row);
                            });
                        }
                        currentPage = payload.transactions.current_page;
                        lastPage = payload.transactions.last_page;
                        document.getElementById('history-page-label').textContent = `${currentPage} / ${lastPage}`;
                        document.getElementById('history-previous').disabled = currentPage <= 1;
                        document.getElementById('history-next').disabled = currentPage >= lastPage;
                    } catch (error) {
                        rows.replaceChildren();
                        const row = document.createElement('tr');
                        const cell = document.createElement('td');
                        cell.colSpan = 5;
                        cell.className = 'px-3 py-5 text-center text-red-700';
                        cell.textContent = error.message;
                        row.append(cell);
                        rows.append(row);
                    }
                }

                function openModal(card, type = 'DEPOSIT') {
                    currentMemberId = card.dataset.memberId;
                    currentPage = 1;
                    document.getElementById('modal-member-name').textContent = card.querySelector('h2').textContent;
                    document.getElementById('modal-member-code').textContent = `Anggota ${card.dataset.memberCode} · ${card.dataset.memberStatus}`;
                    document.getElementById('modal-balance').textContent = card.querySelector('.member-balance').textContent;
                    document.getElementById('transaction-type').value = type;
                    document.getElementById('transaction-category').value = '';
                    document.getElementById('history-filter').value = 'HISTORY';
                    document.getElementById('transaction-amount').value = '';
                    document.getElementById('transaction-note').value = '';
                    document.getElementById('form-message').classList.add('hidden');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    loadMember();
                    document.getElementById('transaction-amount').focus();
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    currentMemberId = null;
                }

                grid.addEventListener('click', (event) => {
                    const button = event.target.closest('button[data-action]');
                    if (!button) return;
                    const card = button.closest('[data-member-id]');
                    if (button.dataset.action === 'open') openModal(card, button.dataset.type);
                    if (button.dataset.action === 'history') openModal(card, 'DEPOSIT');
                });
                modal.addEventListener('click', (event) => {
                    if (event.target === modal || event.target.closest('[data-close-modal]')) closeModal();
                });
                document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && currentMemberId) closeModal(); });
                document.getElementById('history-previous').addEventListener('click', () => loadMember(Math.max(1, currentPage - 1)));
                document.getElementById('history-next').addEventListener('click', () => loadMember(Math.min(lastPage, currentPage + 1)));
                document.getElementById('history-filter').addEventListener('change', () => loadMember(1));

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const submit = document.getElementById('submit-transaction');
                    const message = document.getElementById('form-message');
                    submit.disabled = true;
                    submit.textContent = 'Menyimpan…';
                    message.classList.add('hidden');
                    try {
                        const transactionData = {
                            type: document.getElementById('transaction-type').value,
                            category: document.getElementById('transaction-category').value,
                            amount: document.getElementById('transaction-amount').value,
                            note: document.getElementById('transaction-note').value,
                        };
                        const requestFingerprint = JSON.stringify([currentMemberId, transactionData.type, transactionData.category, transactionData.amount, transactionData.note]);
                        if (!pendingTransaction || pendingTransaction.fingerprint !== requestFingerprint) {
                            pendingTransaction = { fingerprint: requestFingerprint, key: uuid() };
                        }
                        const response = await fetch(`${apiBase}/${currentMemberId}`, {
                            method: 'POST',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ ...transactionData, idempotency_key: pendingTransaction.key }),
                        });
                        const payload = await response.json();
                        if (!response.ok) {
                            const validation = payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.message;
                            throw new Error(validation || 'Transaksi gagal disimpan.');
                        }
                        message.textContent = `Transaksi tersimpan. Saldo terbaru Rp ${formatMoney(payload.balance)}.`;
                        message.className = 'rounded-lg bg-[#e9f6ed] px-3 py-2 text-sm text-[#17633a]';
                        pendingTransaction = null;
                        document.getElementById('modal-balance').textContent = `Rp ${formatMoney(payload.balance)}`;
                        await loadMember(1, false);
                        await refreshSnapshot();
                        document.getElementById('transaction-amount').value = '';
                        document.getElementById('transaction-category').value = '';
                        document.getElementById('transaction-note').value = '';
                    } catch (error) {
                        message.textContent = error.message;
                        message.className = 'rounded-lg bg-[#fff2f0] px-3 py-2 text-sm text-[#a0271d]';
                    } finally {
                        submit.disabled = false;
                        submit.textContent = 'Simpan transaksi';
                    }
                });

                window.setInterval(refreshSnapshot, 5000);
            })();
        </script>
    </body>
</html>
