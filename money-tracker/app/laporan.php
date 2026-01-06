<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /money-tracker/auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function rupiah(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

$ym = $_GET['ym'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');
$start = $ym.'-01';
$end = date('Y-m-t', strtotime($start));

$stmt = $pdo->prepare("
  SELECT
    SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) AS total_income,
    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS total_expense
  FROM transactions
  WHERE user_id=? AND trx_date BETWEEN ? AND ?
");
$stmt->execute([$userId, $start, $end]);
$sum = $stmt->fetch() ?: ['total_income'=>0,'total_expense'=>0];

$totalIncome = (float)$sum['total_income'];
$totalExpense = (float)$sum['total_expense'];
$saldo = $totalIncome - $totalExpense;

// rekap per kategori (expense)
$stmt = $pdo->prepare("
  SELECT COALESCE(c.name,'Tanpa kategori') AS category, SUM(t.amount) AS total
  FROM transactions t
  LEFT JOIN categories c ON c.id=t.category_id
  WHERE t.user_id=? AND t.type='expense' AND t.trx_date BETWEEN ? AND ?
  GROUP BY category
  ORDER BY total DESC
");
$stmt->execute([$userId, $start, $end]);
$rows = $stmt->fetchAll();

$labels = array_map(fn($x)=>$x['category'], $rows);
$totals = array_map(fn($x)=>(float)$x['total'], $rows);

$title = "Laporan";
$active = "laporan";
require_once __DIR__ . '/../partials/header.php';
?>
<div class="appx">
  <?php require_once __DIR__ . '/../partials/sidebar.php'; ?>
  <main class="mainx">
    <?php require_once __DIR__ . '/../partials/topbar.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end mt-3 mb-3 gap-2">
      <div>
        <div class="page-title fs-3">Laporan Bulanan</div>
        <div class="muted">Rekap pengeluaran per kategori + ringkasan.</div>
      </div>
      <form method="get" class="d-flex gap-2">
        <input type="month" name="ym" class="form-control" style="max-width:170px" value="<?= h($ym) ?>">
        <button class="btn btn-primary">Terapkan</button>
      </form>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="cardx"><div class="card-pad">
          <div class="muted small">Total Pemasukan</div>
          <div class="fs-4 fw-bold"><?= rupiah($totalIncome) ?></div>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="cardx"><div class="card-pad">
          <div class="muted small">Total Pengeluaran</div>
          <div class="fs-4 fw-bold"><?= rupiah($totalExpense) ?></div>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="cardx"><div class="card-pad">
          <div class="muted small">Saldo</div>
          <div class="fs-4 fw-bold"><?= rupiah($saldo) ?></div>
        </div></div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-4">
        <div class="cardx">
          <div class="card-pad">
            <div class="fw-bold mb-2">Komposisi Pengeluaran</div>
            <canvas id="donut" height="220"></canvas>
            <div class="muted small mt-2">Jika kosong, berarti belum ada pengeluaran di bulan ini.</div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="cardx">
          <div class="card-pad">
            <div class="fw-bold mb-2">Rekap per Kategori (Expense)</div>

            <?php if (!$rows): ?>
              <div class="muted">Belum ada data pengeluaran.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Kategori</th>
                      <th class="text-end">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($rows as $r): ?>
                      <tr>
                        <td class="fw-semibold"><?= h($r['category']) ?></td>
                        <td class="text-end"><?= rupiah((float)$r['total']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>

    <script>
      const labels = <?= json_encode($labels) ?>;
      const totals = <?= json_encode($totals) ?>;

      const el = document.getElementById('donut');
      new Chart(el, {
        type: 'doughnut',
        data: { labels, datasets: [{ data: totals, borderWidth: 0 }] },
        options: {
          responsive: true,
          cutout: '65%',
          plugins: { legend: { position: 'bottom' } }
        }
      });
    </script>

  </main>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
