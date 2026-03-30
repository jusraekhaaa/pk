<?php
/**
 * TOKO FHIKA - Halaman Sukses Transaksi
 * File: transaksi_sukses.php
 */
require_once 'koneksi.php';
cekLogin();

$kode = $_GET['kode'] ?? '';

// Ambil data transaksi dari session atau database
$trx = null;
if (isset($_SESSION['last_transaction']) && $_SESSION['last_transaction']['kode'] === $kode) {
    $trx = $_SESSION['last_transaction'];
} elseif ($kode) {
    // Ambil dari database
    $stmt = $koneksi->prepare("
        SELECT t.*, u.nama AS kasir_nama
        FROM transactions t
        JOIN users u ON u.id = t.kasir_id
        WHERE t.kode_transaksi = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $trx_db = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($trx_db) {
        // Ambil items
        $stmt2 = $koneksi->prepare("SELECT * FROM transaction_details WHERE transaction_id = ?");
        $stmt2->bind_param('i', $trx_db['id']);
        $stmt2->execute();
        $items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        $trx = [
            'kode'          => $trx_db['kode_transaksi'],
            'total_belanja' => $trx_db['total_belanja'],
            'total_bayar'   => $trx_db['total_bayar'],
            'kembalian'     => $trx_db['kembalian'],
            'metode_bayar'  => $trx_db['metode_bayar'],
            'kasir'         => $trx_db['kasir_nama'],
            'items'         => $items,
        ];
    }
}

if (!$trx) {
    redirect(BASE_URL . 'index.php');
}

// Clear session transaction data
unset($_SESSION['last_transaction']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berhasil - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .receipt-container {
            max-width: 480px;
            margin: 48px auto;
            padding: 0 16px;
        }
        .receipt-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1.5px solid var(--border-color);
        }
        .receipt-header {
            background: linear-gradient(135deg, var(--primary), #7C3AED);
            padding: 32px 28px 24px;
            text-align: center;
            color: white;
        }
        .success-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 16px;
            animation: pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes pop {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .receipt-body {
            padding: 24px 28px;
        }
        .receipt-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.875rem;
            border-bottom: 1px dashed var(--border-color);
        }
        .receipt-info-row:last-child {
            border-bottom: none;
        }
        .receipt-label {
            color: var(--text-muted);
            font-weight: 500;
        }
        .receipt-value {
            font-weight: 700;
            color: var(--text-primary);
        }
        .receipt-items {
            padding: 16px 28px;
            background: var(--bg-main);
            border-top: 1px solid var(--border-color);
        }
        .receipt-item-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 5px 0;
        }
        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 28px;
            border-top: 2px solid var(--border-color);
            font-weight: 800;
            font-size: 1.1rem;
        }
        .receipt-kembalian {
            display: flex;
            justify-content: space-between;
            padding: 12px 28px;
            background: var(--success-light);
            font-size: 0.95rem;
        }
        .receipt-footer {
            padding: 20px 28px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        .serrated-edge {
            height: 12px;
            background: radial-gradient(circle at 50% 100%, white 60%, var(--bg-main) 60%) 0 0 / 20px 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .receipt-container { margin: 0; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar-tokofhika no-print">
    <div style="max-width:1400px; margin:0 auto; padding:0 24px; display:flex; align-items:center; gap:16px;">
        <a href="index.php" class="navbar-brand">TOKO<span>FHIKA</span></a>
        <div style="margin-left:auto; display:flex; gap:12px;">
            <a href="index.php" class="btn-primary-sm">
                <i class="bi bi-house"></i> Beranda
            </a>
            <a href="logout.php" style="font-size:0.82rem; color:var(--text-muted); font-weight:600; display:flex; align-items:center;">
                <i class="bi bi-box-arrow-right" style="font-size:1.1rem;"></i>
            </a>
        </div>
    </div>
</nav>

<div class="receipt-container">
    <!-- Struk / Receipt Card -->
    <div class="receipt-card" id="receiptCard">

        <!-- Header -->
        <div class="receipt-header">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:6px;">Transaksi Berhasil!</h1>
            <p style="font-size:0.85rem; opacity:0.85; margin:0;">Pembayaran telah diterima</p>
        </div>

        <!-- Store Info -->
        <div class="receipt-body">
            <div style="text-align:center; margin-bottom:16px; padding-bottom:16px; border-bottom:1px dashed var(--border-color);">
                <div style="font-size:1.1rem; font-weight:800; letter-spacing:-0.5px;"><?= APP_NAME ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted);">Toko Grosir & Warung Kelontong</div>
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                    <?= date('d/m/Y, H:i') ?> WIB
                </div>
            </div>

            <!-- Info Rows -->
            <div class="receipt-info-row">
                <span class="receipt-label">No. Kwitansi</span>
                <span class="receipt-value" style="color:var(--primary); font-family:monospace; font-size:0.875rem;">
                    <?= htmlspecialchars($trx['kode']) ?>
                </span>
            </div>
            <div class="receipt-info-row">
                <span class="receipt-label">Kasir</span>
                <span class="receipt-value"><?= htmlspecialchars($trx['kasir']) ?></span>
            </div>
            <div class="receipt-info-row">
                <span class="receipt-label">Metode Bayar</span>
                <span class="receipt-value" style="text-transform:capitalize;">
                    <?= match($trx['metode_bayar']) {
                        'tunai'    => '💵 Tunai',
                        'transfer' => '🏦 Transfer Bank',
                        'qris'     => '📱 QRIS',
                        default    => htmlspecialchars($trx['metode_bayar'])
                    } ?>
                </span>
            </div>
            <div class="receipt-info-row">
                <span class="receipt-label">Tanggal & Waktu</span>
                <span class="receipt-value"><?= date('d/m/Y H:i:s') ?></span>
            </div>
        </div>

        <!-- Serrated edge -->
        <div class="serrated-edge"></div>

        <!-- Items -->
        <div class="receipt-items">
            <div style="font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">Detail Pembelian</div>
            <?php foreach ($trx['items'] as $item): ?>
                <div class="receipt-item-row">
                    <div>
                        <div style="font-weight:600; color:var(--text-primary);">
                            <?= htmlspecialchars($item['nama_produk']) ?>
                        </div>
                        <div style="color:var(--text-muted); font-size:0.78rem;">
                            <?= $item['jumlah'] ?> x <?= formatRupiah($item['harga_jual']) ?>
                        </div>
                    </div>
                    <div style="font-weight:700; color:var(--text-primary); white-space:nowrap;">
                        <?= formatRupiah($item['subtotal'] ?? ($item['harga_jual'] * $item['jumlah'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Serrated edge -->
        <div class="serrated-edge" style="transform:rotate(180deg);"></div>

        <!-- Total -->
        <div class="receipt-total-row">
            <span>Total Belanja</span>
            <span style="color:var(--primary);"><?= formatRupiah($trx['total_belanja']) ?></span>
        </div>
        <div class="receipt-info-row" style="padding: 0 28px; margin-bottom: 0; border-bottom: none; border-top: 1px dashed var(--border-color); padding-top: 10px; padding-bottom: 10px;">
            <span class="receipt-label">Dibayar</span>
            <span class="receipt-value"><?= formatRupiah($trx['total_bayar']) ?></span>
        </div>
        <div class="receipt-kembalian">
            <span style="font-weight:700; color:#065F46;">
                <i class="bi bi-arrow-return-left"></i> Kembalian
            </span>
            <span style="font-weight:800; color:var(--success); font-size:1.1rem;">
                <?= formatRupiah($trx['kembalian']) ?>
            </span>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:16px;">
                🙏 Terima kasih telah berbelanja di <strong>Toko Fhika</strong>!<br>
                Barang yang sudah dibeli tidak dapat dikembalikan.
            </div>

            <!-- Action Buttons -->
            <div class="no-print" style="display:flex; flex-direction:column; gap:10px;">
                <button onclick="window.print()" class="btn-primary-custom" style="width:100%; justify-content:center;">
                    <i class="bi bi-printer"></i> Cetak Struk
                </button>
                <a href="index.php" class="btn-secondary-custom" style="width:100%; justify-content:center; text-align:center;">
                    <i class="bi bi-plus-circle"></i> Transaksi Baru
                </a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/transaksi.php" class="btn-secondary-custom" style="width:100%; justify-content:center; text-align:center;">
                        <i class="bi bi-clock-history"></i> Lihat Riwayat
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
