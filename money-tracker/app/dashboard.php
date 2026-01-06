<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }

require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];

// pilih bulan (YYYY-MM)
$ym = $_GET['ym'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');
$start = $ym . '-01';
$end = date('Y-m-t', strtotime($start));

function rupiah(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

// --- ringkasan bulan ---
$stmt = $pdo->prepare("
  SELECT
    SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS total_income,
    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_expense
  FROM transactions
  WHERE user_id = ? AND trx_date BETWEEN ? AND ?
");
$stmt->execute([$userId, $start, $end]);
$sum = $stmt->fetch() ?: ['total_income'=>0,'total_expense'=>0];

$totalIncome = (float)($sum['total_income'] ?? 0);
$totalExpense = (float)($sum['total_expense'] ?? 0);
$saldoBulan = $totalIncome - $totalExpense;

// --- transaksi terbaru ---
$stmt = $pdo->prepare("
  SELECT t.*, c.name AS category_name
  FROM transactions t
  LEFT JOIN categories c ON c.id = t.category_id
  WHERE t.user_id = ?
  ORDER BY t.trx_date DESC, t.id DESC
  LIMIT 6
");
$stmt->execute([$userId]);
$latest = $stmt->fetchAll();

// --- chart 7 hari terakhir (income & expense per hari) ---
$days = 7;
$fromDate = date('Y-m-d', strtotime("-".($days-1)." days"));
$stmt = $pdo->prepare("
  SELECT trx_date,
    SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS income,
    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense
  FROM transactions
  WHERE user_id=? AND trx_date BETWEEN ? AND ?
  GROUP BY trx_date
  ORDER BY trx_date ASC
");
$stmt->execute([$userId, $fromDate, date('Y-m-d')]);
$raw = $stmt->fetchAll();

// normalisasi agar selalu ada 7 titik
$map = [];
foreach ($raw as $r) $map[$r['trx_date']] = $r;

$labels = [];
$incomeData = [];
$expenseData = [];
for ($i=0; $i<$days; $i++){
  $d = date('Y-m-d', strtotime($fromDate." +$i days"));
  $labels[] = date('D', strtotime($d)); // Mon/Tue...
  $incomeData[] = isset($map[$d]) ? (float)$map[$d]['income'] : 0;
  $expenseData[] = isset($map[$d]) ? (float)$map[$d]['expense'] : 0;
}

// --- donut: pengeluaran per kategori bulan ini ---
$stmt = $pdo->prepare("
  SELECT COALESCE(c.name,'Tanpa kategori') AS category, SUM(t.amount) AS total
  FROM transactions t
  LEFT JOIN categories c ON c.id=t.category_id
  WHERE t.user_id=? AND t.type='expense' AND t.trx_date BETWEEN ? AND ?
  GROUP BY category
  ORDER BY total DESC
  LIMIT 8
");
$stmt->execute([$userId, $start, $end]);
$catRows = $stmt->fetchAll();

$catLabels = array_map(fn($x)=>$x['category'], $catRows);
$catTotals = array_map(fn($x)=>(float)$x['total'], $catRows);

$title = "Dashboard";
$active = 'dashboard';

require_once __DIR__ . '/../partials/header.php';
?>
<div class="appx">
  <?php require_once __DIR__ . '/../partials/sidebar.php'; ?>

  <main class="mainx">
    <?php require_once __DIR__ . '/../partials/topbar.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-3 mb-3">
      <div>
        <div class="page-title fs-3">Money Status</div>
        <div class="muted">Ringkasan keuangan dan grafik singkat</div>
      </div>
      <form method="get" class="d-flex gap-2 align-items-center">
        <input type="month" name="ym" class="form-control" style="max-width: 170px;" value="<?= h($ym) ?>">
        <button class="btn btn-primary">Terapkan</button>
      </form>
    </div>

    <!-- Stat cards -->
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="cardx">
          <div class="card-pad">
            <div class="muted small">Pemasukan (bulan ini)</div>
            <div class="fs-4 fw-bold"><?= rupiah($totalIncome) ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="cardx">
          <div class="card-pad">
            <div class="muted small">Pengeluaran (bulan ini)</div>
            <div class="fs-4 fw-bold"><?= rupiah($totalExpense) ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="cardx">
          <div class="card-pad">
            <div class="muted small">Saldo (bulan ini)</div>
            <div class="fs-4 fw-bold"><?= rupiah($saldoBulan) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="row g-3 mb-3">
      <div class="col-lg-8">
        <div class="cardx">
          <div class="card-pad">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-bold">Grafik 7 Hari Terakhir</div>
              <div class="muted small">Income vs Expense</div>
            </div>
            <canvas id="barWeekly" height="120"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="cardx">
          <div class="card-pad">
            <div class="fw-bold mb-2">Pengeluaran per Kategori</div>
            <canvas id="donutCat" height="180"></canvas>
            <div class="muted small mt-2">Top 8 kategori bulan ini</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Latest transactions -->
    <div class="cardx">
      <div class="card-pad">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-bold">Transaksi Terbaru</div>
          <a class="btn btn-outline-secondary btn-sm" href="/money-tracker/app/transaksi_list.php">Lihat semua</a>
        </div>

        <?php if (!$latest): ?>
          <div class="muted">Belum ada transaksi. Klik “Tambah” di sidebar untuk mulai.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Tipe</th>
                  <th>Kategori</th>
                  <th class="text-end">Nominal</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($latest as $t): ?>
                <tr>
                  <td><?= h($t['trx_date']) ?></td>
                  <td>
                    <?php if ($t['type'] === 'income'): ?>
                      <span class="badge badge-income">Pemasukan</span>
                    <?php else: ?>
                      <span class="badge badge-expense">Pengeluaran</span>
                    <?php endif; ?>
                  </td>
                  <td><?= h($t['category_name'] ?? '-') ?></td>
                  <td class="text-end"><?= rupiah((float)$t['amount']) ?></td>
                  <td><?= h($t['note'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <script>
      // Bar weekly
      const labels = <?= json_encode($labels) ?>;
      const incomeData = <?= json_encode($incomeData) ?>;
      const expenseData = <?= json_encode($expenseData) ?>;

      new Chart(document.getElementById('barWeekly'), {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'Pemasukan', data: incomeData, borderWidth: 0, borderRadius: 10 },
            { label: 'Pengeluaran', data: expenseData, borderWidth: 0, borderRadius: 10 }
          ]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'top' } },
          scales: { y: { beginAtZero: true } }
        }
      });

      // Donut category
      const catLabels = <?= json_encode($catLabels) ?>;
      const catTotals = <?= json_encode($catTotals) ?>;

      new Chart(document.getElementById('donutCat'), {
        type: 'doughnut',
        data: {
          labels: catLabels,
          datasets: [{ data: catTotals, borderWidth: 0 }]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          cutout: '65%'
        }
      });
    </script>

  </main>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
