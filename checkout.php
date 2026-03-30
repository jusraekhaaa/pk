<?php
/**
 * TOKO FHIKA - API Checkout (AJAX Endpoint)
 * File: checkout.php
 * Deskripsi: Menerima data keranjang dari JS, memvalidasi stok, menyimpan transaksi ke DB.
 * Method: POST | Content-Type: application/json
 */
require_once 'koneksi.php';
cekLogin();

header('Content-Type: application/json');

// ============================================================
// HANYA MENERIMA REQUEST POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

// ============================================================
// DECODE JSON BODY
// ============================================================
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Data keranjang kosong atau tidak valid.']);
    exit;
}

$cart       = $data['cart'];
$total_bayar = (int)($data['total_bayar'] ?? 0);
$metode_bayar = in_array($data['metode_bayar'] ?? '', ['tunai','transfer','qris'])
                ? $data['metode_bayar']
                : 'tunai';

$kasir_id = $_SESSION['user_id'];

// ============================================================
// MULAI TRANSAKSI DB (untuk konsistensi data)
// ============================================================
$koneksi->begin_transaction();

try {
    $total_belanja = 0;
    $validated_items = [];

    // ---- Validasi setiap item ----
    foreach ($cart as $item) {
        $product_id = (int)($item['id'] ?? 0);
        $jumlah     = (int)($item['jumlah'] ?? 0);

        if ($product_id <= 0 || $jumlah <= 0) {
            throw new Exception("Data item tidak valid (ID: $product_id).");
        }

        // Kunci baris untuk mencegah race condition (SELECT ... FOR UPDATE)
        $stmt = $koneksi->prepare(
            "SELECT id, nama, harga_jual, stok FROM products WHERE id = ? AND is_active = 1 LIMIT 1 FOR UPDATE"
        );
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception("Produk ID $product_id tidak ditemukan atau tidak aktif.");
        }

        if ($product['stok'] < $jumlah) {
            throw new Exception(
                "Stok produk \"" . $product['nama'] . "\" tidak cukup. " .
                "Tersedia: " . $product['stok'] . ", diminta: $jumlah."
            );
        }

        $subtotal = $product['harga_jual'] * $jumlah;
        $total_belanja += $subtotal;

        $validated_items[] = [
            'product_id'  => $product['id'],
            'nama_produk' => $product['nama'],
            'harga_jual'  => $product['harga_jual'],
            'jumlah'      => $jumlah,
            'subtotal'    => $subtotal,
            'stok_sebelum'=> $product['stok'],
        ];
    }

    // Cek jumlah bayar
    if ($total_bayar < $total_belanja) {
        throw new Exception("Jumlah bayar tidak mencukupi.");
    }

    $kembalian = $total_bayar - $total_belanja;
    $kode_transaksi = generateKodeTransaksi();

    // ---- Simpan header transaksi ----
    $stmt = $koneksi->prepare(
        "INSERT INTO transactions (kode_transaksi, kasir_id, total_belanja, total_bayar, kembalian, metode_bayar)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('siddds', $kode_transaksi, $kasir_id, $total_belanja, $total_bayar, $kembalian, $metode_bayar);
    $stmt->execute();
    $transaction_id = $koneksi->insert_id;
    $stmt->close();

    // ---- Simpan detail & update stok ----
    foreach ($validated_items as $item) {
        // Insert detail
        $stmt = $koneksi->prepare(
            "INSERT INTO transaction_details (transaction_id, product_id, nama_produk, harga_jual, jumlah, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iisdid', $transaction_id, $item['product_id'], $item['nama_produk'], $item['harga_jual'], $item['jumlah'], $item['subtotal']);
        $stmt->execute();
        $stmt->close();

        // Kurangi stok
        $stmt = $koneksi->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
        $stmt->bind_param('ii', $item['jumlah'], $item['product_id']);
        $stmt->execute();
        $stmt->close();

        // Log mutasi stok
        $stok_sesudah = $item['stok_sebelum'] - $item['jumlah'];
        $keterangan   = "Penjualan: $kode_transaksi";
        $tipe         = 'keluar';
        $stmt = $koneksi->prepare(
            "INSERT INTO stock_mutations (product_id, tipe, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id, transaction_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('isiiiisi', $item['product_id'], $tipe, $item['jumlah'], $item['stok_sebelum'], $stok_sesudah, $keterangan, $kasir_id, $transaction_id);
        $stmt->execute();
        $stmt->close();
    }

    // ---- Commit ----
    $koneksi->commit();

    // ---- Simpan ke session untuk halaman sukses ----
    $_SESSION['last_transaction'] = [
        'kode'          => $kode_transaksi,
        'total_belanja' => $total_belanja,
        'total_bayar'   => $total_bayar,
        'kembalian'     => $kembalian,
        'metode_bayar'  => $metode_bayar,
        'kasir'         => $_SESSION['user_nama'],
        'items'         => $validated_items,
    ];

    echo json_encode([
        'success'         => true,
        'kode_transaksi'  => $kode_transaksi,
        'total_belanja'   => $total_belanja,
        'kembalian'       => $kembalian,
        'message'         => 'Transaksi berhasil!'
    ]);

} catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
