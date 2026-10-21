import { FormEvent, useMemo, useState } from "react";
import { DESKTOP_ROLES, UserRole, canAccessPlatform, getLoanRiskColor } from "@koperasi/shared";

type DesktopRole = Exclude<UserRole, "CUSTOMER">;
type DesktopPage = "dashboard" | "loans" | "attendance" | "history" | "settings";

const roleLabels: Record<DesktopRole, string> = {
  COLLECTOR: "Karyawan / Penagih",
  ADMIN: "Admin",
  MASTER_ADMIN: "Master Admin"
};

const navItems: Array<{ id: DesktopPage; label: string; icon: string }> = [
  { id: "dashboard", label: "Dashboard", icon: "home" },
  { id: "loans", label: "Peminjaman", icon: "wallet" },
  { id: "attendance", label: "Absensi", icon: "calendar" },
  { id: "history", label: "Riwayat", icon: "clock" },
  { id: "settings", label: "Pengaturan", icon: "gear" }
];

const applicationCards = [
  { name: "Mas. Cecep", place: "Bacukiki, Parepare", submittedAt: "10 September 2025", verified: false },
  { name: "Ambo Dalle", place: "Soreang, Parepare", submittedAt: "8 September 2025", verified: true },
  { name: "Rudi Sappo", place: "Lumpue, Parepare", submittedAt: "7 September 2025", verified: true },
  { name: "Fikrank", place: "Lapadde, Parepare", submittedAt: "6 September 2025", verified: false }
];

const customers = [
  {
    id: 1,
    name: "Mas. Cecep",
    collector: "Karyawan 1",
    place: "Bacukiki, Parepare",
    amount: "Rp 8.000.000",
    remaining: "Rp 5.600.000",
    streak: 0,
    arrears: 7,
    scanned: false,
    status: "Belum Lunas",
    lastPaidAt: "-"
  },
  {
    id: 2,
    name: "Fikrank",
    collector: "Karyawan 1",
    place: "Perumnas Parepare",
    amount: "Rp 10.000.000",
    remaining: "Rp 8.100.000",
    streak: 4,
    arrears: 5,
    scanned: false,
    status: "Belum Lunas",
    lastPaidAt: "-"
  },
  {
    id: 3,
    name: "Sheif",
    collector: "Karyawan 2",
    place: "Perumnas Parepare",
    amount: "Rp 10.000.000",
    remaining: "Rp 9.500.000",
    streak: 9,
    arrears: 1,
    scanned: false,
    status: "Belum Lunas",
    lastPaidAt: "-"
  },
  {
    id: 4,
    name: "Gian",
    collector: "Karyawan 2",
    place: "Tegal, Parepare",
    amount: "Rp 10.000.000",
    remaining: "Rp 0",
    streak: 21,
    arrears: 0,
    scanned: true,
    status: "Lunas",
    lastPaidAt: "08.00 P.M."
  },
  {
    id: 5,
    name: "Hj. Samsul",
    collector: "Karyawan 3",
    place: "Tegal, Parepare",
    amount: "Rp 10.000.000",
    remaining: "Rp 0",
    streak: 19,
    arrears: 0,
    scanned: true,
    status: "Lunas",
    lastPaidAt: "07.51 P.M."
  }
];

const attendanceDays = [
  { day: 1, color: "blue", status: "Lengkap", slots: "4/4" },
  { day: 2, color: "blue", status: "Lengkap", slots: "4/4" },
  { day: 3, color: "yellow", status: "Parsial", slots: "2/4" },
  { day: 4, color: "red", status: "Alpha", slots: "0/4" },
  { day: 5, color: "muted", status: "Minggu", slots: "-" },
  { day: 6, color: "blue", status: "Lengkap", slots: "4/4" },
  { day: 7, color: "yellow", status: "Parsial", slots: "1/4" },
  { day: 8, color: "blue", status: "Lengkap", slots: "4/4" },
  { day: 9, color: "blue", status: "Lengkap", slots: "4/4" },
  { day: 10, color: "red", status: "Alpha", slots: "0/4" },
  { day: 11, color: "muted", status: "Minggu", slots: "-" },
  { day: 12, color: "blue", status: "Lengkap", slots: "4/4" },
  { day: 13, color: "yellow", status: "Parsial", slots: "3/4" },
  { day: 14, color: "blue", status: "Lengkap", slots: "4/4" }
];

const historyCards = [
  { name: "Gian", place: "Bacukiki, Parepare", ago: "10 Days Ago" },
  { name: "Yanto", place: "Lapadde, Parepare", ago: "13 Days Ago" },
  { name: "Mulyadi Asep", place: "Bacukiki, Parepare", ago: "2 Month Ago" }
];

const monthlyRows = [
  { day: "Day-21", name: "Mas. Cecep", nik: "231011098", date: "01/10/2025", status: "Belum Lunas", time: "-" },
  { day: "Day-20", name: "Mas. Cecep", nik: "231011098", date: "01/10/2025", status: "Lunas", time: "08.00 P.M." },
  { day: "Day-19", name: "Mas. Cecep", nik: "231011098", date: "30/09/2025", status: "Lunas", time: "07.51 P.M." }
];

export function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [activeRole, setActiveRole] = useState<DesktopRole>("COLLECTOR");
  const [activePage, setActivePage] = useState<DesktopPage>("dashboard");
  const [selectedCustomerId, setSelectedCustomerId] = useState(1);

  const desktopRoles = useMemo(
    () => DESKTOP_ROLES.filter((role): role is DesktopRole => role !== "CUSTOMER" && canAccessPlatform(role, "DESKTOP")),
    []
  );

  const selectedCustomer = customers.find((customer) => customer.id === selectedCustomerId) ?? customers[0];

  if (!isLoggedIn) {
    return <LoginPage activeRole={activeRole} onRoleChange={setActiveRole} onLogin={() => setIsLoggedIn(true)} roles={desktopRoles} />;
  }

  return (
    <main className="app-frame">
      <TopNavigation
        activePage={activePage}
        activeRole={activeRole}
        navItems={navItems}
        onNavigate={setActivePage}
        onRoleChange={setActiveRole}
        roles={desktopRoles}
      />

      {activePage === "dashboard" && <DashboardPage activeRole={activeRole} />}
      {activePage === "loans" && (
        <LoansPage selectedCustomer={selectedCustomer} selectedCustomerId={selectedCustomerId} onSelectCustomer={setSelectedCustomerId} />
      )}
      {activePage === "attendance" && <AttendancePage activeRole={activeRole} />}
      {activePage === "history" && <HistoryPage />}
      {activePage === "settings" && <SettingsPage activeRole={activeRole} />}
    </main>
  );
}

function LoginPage({
  activeRole,
  onLogin,
  onRoleChange,
  roles
}: {
  activeRole: DesktopRole;
  onLogin: () => void;
  onRoleChange: (role: DesktopRole) => void;
  roles: DesktopRole[];
}) {
  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    onLogin();
  }

  return (
    <main className="login-page">
      <section className="login-art">
        <div className="skyline">
          <span className="tower tower-one" />
          <span className="tower tower-two" />
          <span className="tower tower-three" />
          <span className="tower tower-four" />
        </div>
        <KoperasiSeal size="large" />
      </section>

      <section className="login-panel">
        <form className="login-card" onSubmit={handleSubmit}>
          <h1>Login</h1>
          <label>
            Username
            <input placeholder="Enter your Username here" defaultValue="karyawan1" />
          </label>
          <label>
            Password
            <input placeholder="Enter your Password here" type="password" defaultValue="password" />
          </label>

          <div className="login-role-grid" aria-label="Pilih role desktop">
            {roles.map((role) => (
              <button
                className={activeRole === role ? "mini-role active" : "mini-role"}
                key={role}
                onClick={() => onRoleChange(role)}
                type="button"
              >
                {roleLabels[role]}
              </button>
            ))}
          </div>

          <button className="login-button" type="submit">Enter</button>
          <p className="help-text">Any problem? <span>contact centre</span></p>
        </form>

        <strong className="login-brand">Koperasi Harmoni</strong>
      </section>
    </main>
  );
}

function TopNavigation({
  activePage,
  activeRole,
  navItems,
  onNavigate,
  onRoleChange,
  roles
}: {
  activePage: DesktopPage;
  activeRole: DesktopRole;
  navItems: Array<{ id: DesktopPage; label: string; icon: string }>;
  onNavigate: (page: DesktopPage) => void;
  onRoleChange: (role: DesktopRole) => void;
  roles: DesktopRole[];
}) {
  return (
    <header className="top-nav">
      <KoperasiSeal />
      <nav>
        {navItems.map((item) => (
          <button className={activePage === item.id ? "nav-item active" : "nav-item"} key={item.id} onClick={() => onNavigate(item.id)}>
            <span className={`line-icon ${item.icon}`} />
            {item.label}
          </button>
        ))}
      </nav>

      <select className="role-select" onChange={(event) => onRoleChange(event.target.value as DesktopRole)} value={activeRole}>
        {roles.map((role) => (
          <option key={role} value={role}>{roleLabels[role]}</option>
        ))}
      </select>
    </header>
  );
}

function DashboardPage({ activeRole }: { activeRole: DesktopRole }) {
  const paidToday = customers.filter((customer) => customer.scanned).length;
  const highRisk = customers.filter((customer) => ["RED", "DARK"].includes(getLoanRiskColor(customer.arrears))).length;

  return (
    <section className="page-shell">
      <PageToolbar title={`Home ${roleLabels[activeRole]}`} categoryLabel="Category" />

      <section className="summary-strip">
        <SummaryCard label="Scan hari ini" value={`${paidToday}/${customers.length}`} tone="blue" />
        <SummaryCard label="Belum scan" value={`${customers.length - paidToday}`} tone="yellow" />
        <SummaryCard label="Merah/Hitam" value={highRisk.toString()} tone="red" />
        <SummaryCard label="Total anggota" value="230" tone="dark" />
      </section>

      <section className="dashboard-grid">
        <div className="cards-grid">
          <article className="application-card add-card">
            <div className="plus-circle">+</div>
            <h3>Tambahkan Pengajuan Baru</h3>
            <p>-</p>
            <p>-</p>
            <button>Tambahkan</button>
          </article>

          {applicationCards.map((application) => (
            <ApplicationCard application={application} key={application.name} />
          ))}
        </div>

        <aside className="side-panel">
          <h2>Leaderboard</h2>
          <div className="leaderboard-list">
            {customers.map((customer) => (
              <div className="leaderboard-row" key={customer.id}>
                <RiskDot arrears={customer.arrears} />
                <div>
                  <strong>{customer.name}</strong>
                  <span>{customer.streak} streak - {customer.arrears} tunggakan</span>
                </div>
              </div>
            ))}
          </div>

          <div className="scan-panel">
            <h3>QR hari ini</h3>
            <p><strong>{paidToday}</strong> sudah scan</p>
            <p><strong>{customers.length - paidToday}</strong> belum scan</p>
          </div>
        </aside>
      </section>
    </section>
  );
}

function LoansPage({
  onSelectCustomer,
  selectedCustomer,
  selectedCustomerId
}: {
  onSelectCustomer: (id: number) => void;
  selectedCustomer: (typeof customers)[number];
  selectedCustomerId: number;
}) {
  return (
    <section className="page-shell">
      <PageToolbar title="Peminjaman Karyawan" categoryLabel="Debt" extra={<span className="member-pill">Jumlah Anggota: 230</span>} />

      <section className="loan-card-grid">
        {customers.map((customer) => (
          <LoanCard customer={customer} isActive={selectedCustomerId === customer.id} key={customer.id} onSelect={() => onSelectCustomer(customer.id)} />
        ))}
      </section>

      <section className="detail-layout">
        <article className="borrower-detail-card">
          <button className="back-button">‹</button>
          <div className="document-preview">
            <KoperasiSeal />
            <strong>FORMULIR PERMOHONAN PEMINJAMAN UANG</strong>
            <span>Nama: {selectedCustomer.name}</span>
            <span>Alamat: {selectedCustomer.place}</span>
            <span>Pengajuan: {selectedCustomer.amount}</span>
          </div>

          <div className="borrower-summary">
            <h2>{selectedCustomer.name}</h2>
            <p>{selectedCustomer.place}</p>
            <span>Pengajuan Pinjaman:</span>
            <strong>{selectedCustomer.amount}</strong>
            <small>Diajukan 10 September 2025</small>

            <div className="collateral-row">
              <div className="photo-thumb scooter" />
              <div className="photo-thumb add-thumb">+</div>
            </div>

            <div className="detail-actions">
              <button>Simpan</button>
              <button>Batalkan</button>
            </div>
          </div>
        </article>

        <article className="detail-info">
          <h2>Detail:</h2>
          <ul>
            <li>Alamat: Jl. Semesta C4-20, Ujung, Parepare, Sulawesi Selatan</li>
            <li>Pekerjaan/Usaha: pedagang makanan ringan</li>
            <li>Analisis: layak dilunasi dalam 60 hari dengan cicilan harian.</li>
          </ul>
          <div className="business-photo-row">
            <div className="business-photo stall" />
            <div className="business-photo snack" />
            <div className="business-photo add-thumb">+</div>
          </div>
        </article>

        <article className="monthly-table">
          {monthlyRows.map((row) => (
            <div className="monthly-row" key={row.day}>
              <strong>{row.day}</strong>
              <span>Nama: {row.name}</span>
              <span>NIK: {row.nik}</span>
              <span>{row.date}</span>
              <b className={row.status === "Lunas" ? "paid-text" : "unpaid-text"}>{row.status}</b>
              <small>{row.time}</small>
            </div>
          ))}
        </article>
      </section>
    </section>
  );
}

function AttendancePage({ activeRole }: { activeRole: DesktopRole }) {
  return (
    <section className="page-shell">
      <PageToolbar title={`Absensi ${roleLabels[activeRole]}`} categoryLabel="Mei 2026" />

      <section className="attendance-layout">
        <article className="calendar-panel">
          <div className="calendar-header">
            <h2>Kalender Absensi</h2>
            <div className="legend">
              <span><i className="legend-dot blue" /> Lengkap</span>
              <span><i className="legend-dot yellow" /> Parsial</span>
              <span><i className="legend-dot red" /> Alpha</span>
            </div>
          </div>

          <div className="calendar-grid">
            {attendanceDays.map((day) => (
              <div className={`calendar-day ${day.color}`} key={day.day}>
                <strong>{day.day}</strong>
                <span>{day.status}</span>
                <small>{day.slots}</small>
              </div>
            ))}
          </div>
        </article>

        <aside className="side-panel">
          <h2>Slot hari ini</h2>
          <AttendanceSlot label="Masuk Pagi" time="07.00 - 10.59" status="07.40" tone="blue" />
          <AttendanceSlot label="Keluar Siang" time="11.00 - 15.59" status="12.05" tone="blue" />
          <AttendanceSlot label="Masuk Sore" time="16.00 - 17.29" status="-" tone="yellow" />
          <AttendanceSlot label="Keluar Malam" time="17.30 - 23.59" status="-" tone="red" />
        </aside>
      </section>
    </section>
  );
}

function HistoryPage() {
  return (
    <section className="page-shell">
      <PageToolbar title="History Karyawan" categoryLabel="Category" />

      <section className="history-grid">
        {historyCards.map((item) => (
          <article className="history-card" key={item.name}>
            <span className="time-badge">{item.ago}</span>
            <div className="document-mini">
              <KoperasiSeal />
              <span>FORMULIR PERMOHONAN PEMINJAMAN UANG</span>
            </div>
            <h3>{item.name}</h3>
            <p>{item.place}</p>
            <div className="history-actions">
              <button>Edit</button>
              <button>Remove</button>
            </div>
          </article>
        ))}
      </section>
    </section>
  );
}

function SettingsPage({ activeRole }: { activeRole: DesktopRole }) {
  const canManageAdmins = activeRole === "MASTER_ADMIN";

  return (
    <section className="page-shell">
      <PageToolbar title={`Pengaturan ${roleLabels[activeRole]}`} categoryLabel="Role" />

      <section className="settings-grid">
        <article className="settings-card">
          <h2>Validasi</h2>
          <p>Pengajuan Nasabah dan update data penting masuk ke antrean validasi Admin.</p>
          <button>Review Pengajuan</button>
        </article>

        <article className="settings-card">
          <h2>CRUD Karyawan</h2>
          <p>Admin dan Master Admin dapat menambah, mengubah, menonaktifkan, dan memvalidasi data Karyawan.</p>
          <button>Kelola Karyawan</button>
        </article>

        <article className={canManageAdmins ? "settings-card highlight" : "settings-card locked"}>
          <h2>CRUD Admin</h2>
          <p>{canManageAdmins ? "Master Admin dapat menambahkan Admin baru." : "Hanya Master Admin yang dapat mengelola Admin."}</p>
          <button disabled={!canManageAdmins}>Kelola Admin</button>
        </article>
      </section>
    </section>
  );
}

function PageToolbar({ categoryLabel, extra, title }: { categoryLabel: string; extra?: JSX.Element; title: string }) {
  return (
    <>
      <div className="page-title">{title}</div>
      <div className="toolbar">
        <label className="search-box">
          <span />
          <input placeholder="search" />
        </label>
        <button className="search-button">Search</button>
        <button className="filter-button">{categoryLabel}<small> sliders</small></button>
        {extra}
      </div>
    </>
  );
}

function ApplicationCard({ application }: { application: (typeof applicationCards)[number] }) {
  return (
    <article className="application-card">
      <span className={application.verified ? "status-badge verified" : "status-badge unverified"}>
        {application.verified ? "Verified" : "Not Verified"}
      </span>
      <div className="document-mini">
        <KoperasiSeal />
        <span>FORMULIR PERMOHONAN PEMINJAMAN UANG</span>
      </div>
      <h3>{application.name}</h3>
      <p>{application.place}</p>
      <small>{application.verified ? "Ter-Verifikasi" : "Belum Ter-Verifikasi"}</small>
      <small>Diajukan {application.submittedAt}</small>
      <div className="card-actions">
        <button>Edit</button>
        <button>Cancel</button>
      </div>
    </article>
  );
}

function LoanCard({
  customer,
  isActive,
  onSelect
}: {
  customer: (typeof customers)[number];
  isActive: boolean;
  onSelect: () => void;
}) {
  const isPaid = customer.arrears === 0;

  return (
    <article className={isActive ? "loan-card active" : "loan-card"}>
      <span className={isPaid ? "status-badge paid" : "status-badge overdue"}>{isPaid ? "Paid" : `- ${customer.arrears} day`}</span>
      <h3>{customer.name}</h3>
      <p>{customer.place}</p>
      <small>{customer.amount}</small>
      <div className="loan-actions">
        <button onClick={onSelect}>View</button>
        <button>Verified</button>
      </div>
    </article>
  );
}

function AttendanceSlot({ label, status, time, tone }: { label: string; status: string; time: string; tone: string }) {
  return (
    <div className="attendance-slot">
      <RiskBubble tone={tone} />
      <div>
        <strong>{label}</strong>
        <span>{time}</span>
      </div>
      <b>{status}</b>
    </div>
  );
}

function SummaryCard({ label, tone, value }: { label: string; tone: string; value: string }) {
  return (
    <article className={`summary-card ${tone}`}>
      <span>{label}</span>
      <strong>{value}</strong>
    </article>
  );
}

function RiskDot({ arrears }: { arrears: number }) {
  return <span className={`risk-dot ${getLoanRiskColor(arrears).toLowerCase()}`} />;
}

function RiskBubble({ tone }: { tone: string }) {
  return <span className={`risk-bubble ${tone}`} />;
}

function KoperasiSeal({ size = "normal" }: { size?: "normal" | "large" }) {
  return (
    <div className={`koperasi-seal ${size}`}>
      <div className="seal-ring">
        <strong>KH</strong>
        <span>Koperasi</span>
      </div>
    </div>
  );
}
