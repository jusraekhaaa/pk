<?php
/**
 * TOKO FHIKA - Halaman Utama (Katalog Merk)
 * File: index.php
 * Deskripsi: Menampilkan daftar Merk yang dikelompokkan per Perusahaan Induk.
 *            Mirip dengan referensi Gambar 1.
 */
require_once 'koneksi.php';
cekLogin(); // Pastikan user sudah login

// ============================================================
// AMBIL DATA DARI DATABASE
// ============================================================

// Ambil semua kategori untuk dropdown filter
$q_categories = $koneksi->query("SELECT id, nama FROM categories ORDER BY nama ASC");
$categories = $q_categories ? $q_categories->fetch_all(MYSQLI_ASSOC) : [];

// Filter aktif
$filter_category = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$search_query    = isset($_GET['q']) ? trim($_GET['q']) : '';

// Susun query brands dengan jumlah produk, dikelompokkan per perusahaan
$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search_query !== '') {
    $like = '%' . $koneksi->real_escape_string($search_query) . '%';
    $where .= " AND (b.nama LIKE '$like' OR b.perusahaan LIKE '$like')";
}

$q_brands_sql = "
    SELECT
        b.id,
        b.nama,
        b.slug,
        b.perusahaan,
        b.logo,
        COUNT(p.id) AS jumlah_produk
    FROM brands b
    LEFT JOIN products p ON p.brand_id = b.id AND p.is_active = 1
    " . ($filter_category > 0 ? "AND p.category_id = $filter_category" : "") . "
    $where
    GROUP BY b.id
    HAVING jumlah_produk > 0 OR '$search_query' != ''
    ORDER BY b.perusahaan ASC, b.nama ASC
";

$q_brands = $koneksi->query($q_brands_sql);
$brands_raw = $q_brands ? $q_brands->fetch_all(MYSQLI_ASSOC) : [];

// Kelompokkan brands berdasarkan perusahaan
$brands_by_company = [];
foreach ($brands_raw as $brand) {
    $brands_by_company[$brand['perusahaan']][] = $brand;
}

// Warna gradien auto-assign
$brand_colors = ['brand-color-1','brand-color-2','brand-color-3','brand-color-4',
                 'brand-color-5','brand-color-6','brand-color-7','brand-color-8',
                 'brand-color-9','brand-color-10'];
$color_index = 0;

// Jumlah item di keranjang (dari session PHP)
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['jumlah'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - <?= APP_NAME ?></title>
    <meta name="description" content="Katalog produk Toko Fhika – temukan berbagai merk dari Mayora, Wings, Indofood, Unilever, dan lainnya.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<nav class="navbar-tokofhika">
    <div style="max-width:1400px; margin:0 auto; padding:0 24px; display:flex; align-items:center; gap:16px;">

        <!-- Logo -->
        <a href="index.php" class="navbar-brand" style="white-space:nowrap; margin-right:12px;">
            TOKO<span>FHIKA</span>
        </a>

        <!-- Search & Filter -->
        <form method="GET" action="index.php" class="navbar-search-group" style="flex:1;">
            <input
                type="text"
                class="form-control"
                name="q"
                id="searchBar"
                placeholder="Cari merk produk..."
                value="<?= htmlspecialchars($search_query) ?>"
                autocomplete="off"
            >
            <select class="form-select" name="category_id" id="categoryFilter" onchange="this.form.submit()">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="display:none;"></button>
        </form>

        <!-- Right: User info & Cart -->
        <div style="display:flex; align-items:center; gap:12px; margin-left:auto;">

            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin/dashboard.php" style="font-size:0.82rem; font-weight:600; color:var(--primary); white-space:nowrap;">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            <?php endif; ?>

            <div style="width:1px; height:24px; background:var(--border-color);"></div>

            <!-- User avatar -->
            <div style="display:flex; align-items:center; gap:8px; cursor:default;">
                <div style="width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,var(--primary),#7C3AED); display:flex; align-items:center; justify-content:center; color:white; font-size:0.75rem; font-weight:800;">
                    <?= strtoupper(substr($_SESSION['user_nama'], 0, 2)) ?>
                </div>
                <div style="display:none; flex-direction:column;" class="d-sm-flex">
                    <span style="font-size:0.82rem; font-weight:700; color:var(--text-primary); line-height:1.2;">
                        <?= htmlspecialchars($_SESSION['user_nama']) ?>
                    </span>
                    <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">
                        <?= ucfirst($_SESSION['user_role']) ?>
                    </span>
                </div>
            </div>

            <!-- Cart Button -->
            <button class="nav-cart-btn" id="btnOpenCart" onclick="toggleCart()" aria-label="Buka Keranjang">
                <i class="bi bi-bag-check"></i>
                <span>Keranjang</span>
                <span class="cart-badge <?= $cart_count === 0 ? 'hidden' : '' ?>" id="cartBadge">
                    <?= $cart_count ?>
                </span>
            </button>

            <!-- Logout -->
            <a href="logout.php" style="font-size:0.82rem; color:var(--text-muted); font-weight:600;" title="Logout">
                <i class="bi bi-box-arrow-right" style="font-size:1.1rem;"></i>
            </a>
        </div>
    </div>
</nav>

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<div class="main-content">

    <?php if (!empty($search_query) || $filter_category > 0): ?>
        <!-- Filter info bar -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding:12px 18px; background:var(--primary-light); border-radius:10px; border:1.5px solid rgba(37,99,235,0.2);">
            <div style="font-size:0.875rem; font-weight:600; color:var(--primary);">
                <i class="bi bi-funnel-fill"></i>
                Menampilkan hasil untuk: <strong><?= htmlspecialchars($search_query ?: 'Semua') ?></strong>
                <?php if ($filter_category > 0): ?>
                    | Kategori: <strong><?= htmlspecialchars($categories[array_search($filter_category, array_column($categories,'id'))]['nama'] ?? '') ?></strong>
                <?php endif; ?>
                &mdash; <strong><?= count($brands_raw) ?></strong> merk ditemukan
            </div>
            <a href="index.php" style="font-size:0.82rem; font-weight:600; color:var(--primary);">
                <i class="bi bi-x-circle"></i> Hapus Filter
            </a>
        </div>
    <?php endif; ?>

    <?php if (empty($brands_by_company)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-search"></i></div>
            <h2 class="empty-state-title">Tidak Ada Merk Ditemukan</h2>
            <p class="empty-state-text">Coba gunakan kata kunci pencarian yang berbeda atau hapus filter kategori.</p>
            <a href="index.php" class="btn-primary-sm" style="display:inline-flex; margin:0 auto;">
                <i class="bi bi-arrow-left"></i> Kembali ke Semua Merk
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($brands_by_company as $company_name => $brands): ?>
            <!-- Company Section -->
            <div class="section-company-header">
                <div class="company-title-group">
                    <span class="company-badge">Perusahaan</span>
                    <div>
                        <div class="company-name"><?= htmlspecialchars($company_name) ?></div>
                        <div class="company-brand-count"><?= count($brands) ?> merk tersedia</div>
                    </div>
                </div>
            </div>
            <hr class="section-divider">

            <!-- Brands Grid -->
            <div class="brands-grid" style="margin-bottom:8px;">
                <?php foreach ($brands as $brand):
                    $color_class = $brand_colors[$color_index % count($brand_colors)];
                    $color_index++;
                    $initials = strtoupper(substr($brand['nama'], 0, 2));
                    $url_params = http_build_query([
                        'brand_id' => $brand['id'],
                        'q'        => $search_query,
                    ]);
                ?>
                    <a href="produk.php?brand_id=<?= $brand['id'] ?>" class="brand-card" id="brand-<?= $brand['id'] ?>">
                        <!-- Logo / Placeholder -->
                        <?php if (!empty($brand['logo']) && file_exists(UPLOAD_DIR . $brand['logo'])): ?>
                            <div class="brand-logo-wrapper">
                                <img src="<?= UPLOAD_URL . htmlspecialchars($brand['logo']) ?>" alt="<?= htmlspecialchars($brand['nama']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="brand-logo-placeholder <?= $color_class ?>">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>

                        <div class="brand-name"><?= htmlspecialchars($brand['nama']) ?></div>
                        <div class="brand-product-count">
                            <i class="bi bi-box-seam"></i>
                            <?= $brand['jumlah_produk'] ?> produk
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="margin-bottom: 32px;"></div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<!-- END MAIN CONTENT -->

<!-- ============================================================
     CART SIDEBAR + OVERLAY
     ============================================================ -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>

<div class="cart-panel" id="cartPanel">
    <!-- Header -->
    <div class="cart-panel-header">
        <div>
            <div class="cart-panel-title"><i class="bi bi-bag-check me-2"></i>Keranjang Belanja</div>
            <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;" id="cartItemCount">0 item</div>
        </div>
        <button class="btn-close-cart" onclick="toggleCart()" aria-label="Tutup keranjang">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Items -->
    <div class="cart-items-wrapper" id="cartItemsWrapper">
        <div class="cart-empty-state" id="cartEmptyState">
            <i class="bi bi-bag-x"></i>
            <p>Keranjang masih kosong</p>
            <span style="font-size:0.82rem;">Klik produk untuk menambahkan ke keranjang</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="cart-panel-footer">
        <div class="cart-summary-row">
            <span class="cart-total-label">Total Belanja</span>
            <span class="cart-total-value" id="cartTotal">Rp 0</span>
        </div>
        <div class="cart-summary-row" style="margin-top:4px; padding-top:10px; border-top:1px solid var(--border-color);">
            <span style="font-size:0.78rem; color:var(--text-muted);">* Harga belum termasuk pajak</span>
        </div>
        <button class="btn-checkout" id="btnCheckout" onclick="bukaModalCheckout()" disabled>
            <i class="bi bi-credit-card"></i> Proses Pembayaran
        </button>
        <button class="btn-clear-cart" id="btnClearCart" onclick="kosongkanKeranjang()" style="display:none;">
            <i class="bi bi-trash3"></i> Kosongkan Keranjang
        </button>
    </div>
</div>

<!-- ============================================================
     CHECKOUT MODAL
     ============================================================ -->
<div class="modal-backdrop-custom" id="checkoutModal">
    <div class="modal-box">
        <div class="modal-header-custom">
            <h5><i class="bi bi-receipt me-2"></i>Konfirmasi Pembayaran</h5>
            <button class="btn-close-cart" onclick="tutupModalCheckout()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body-custom">
            <!-- Order Summary -->
            <p style="font-size:0.8rem; font-weight:700; color:var(--text-muted); letter-spacing:0.5px; text-transform:uppercase; margin-bottom:10px;">Ringkasan Pesanan</p>
            <div id="orderSummaryContainer"></div>

            <!-- Total -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-top:2px solid var(--border-color); margin-top:8px;">
                <span style="font-size:1rem; font-weight:700;">TOTAL</span>
                <span style="font-size:1.2rem; font-weight:800; color:var(--primary);" id="modalTotal"></span>
            </div>

            <!-- Metode Bayar -->
            <div class="payment-input-group" style="margin-top:16px;">
                <label class="payment-input-label" for="metodeBayar">Metode Pembayaran</label>
                <select id="metodeBayar" class="form-control-custom" style="margin-bottom:14px; cursor:pointer;">
                    <option value="tunai">💵 Tunai</option>
                    <option value="transfer">🏦 Transfer Bank</option>
                    <option value="qris">📱 QRIS</option>
                </select>

                <label class="payment-input-label" for="inputBayar">Jumlah Dibayar</label>
                <input
                    type="number"
                    id="inputBayar"
                    class="payment-input"
                    placeholder="0"
                    min="0"
                    oninput="hitungKembalian()"
                >

                <div class="kembalian-display" id="kembalianDisplay" style="display:none;">
                    <span class="kembalian-label"><i class="bi bi-arrow-return-left"></i> Kembalian</span>
                    <span class="kembalian-value" id="kembalianValue">Rp 0</span>
                </div>
            </div>
        </div>
        <div class="modal-footer-custom">
            <button class="btn-secondary-custom" onclick="tutupModalCheckout()">
                <i class="bi bi-arrow-left"></i> Batal
            </button>
            <button class="btn-primary-custom" id="btnProsesTransaksi" onclick="prosesTransaksi()">
                <i class="bi bi-check2-circle"></i> Selesaikan Transaksi
            </button>
        </div>
    </div>
</div>

<!-- Scroll to top -->
<button class="scroll-top-btn" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Kembali ke atas">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
// ============================================================
// CART STATE (disimpan di localStorage agar tidak hilang saat navigasi)
// ============================================================
let cart = JSON.parse(localStorage.getItem('tokofhika_cart') || '[]');

// ============================================================
// FUNGSI CART
// ============================================================
function simpanCart() {
    localStorage.setItem('tokofhika_cart', JSON.stringify(cart));
    renderCart();
    updateCartBadge();
}

function tambahKeKeranjang(id, nama, tipe, harga, gambar, stok) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.jumlah < stok) {
            existing.jumlah++;
            showToast(`<i class="bi bi-plus-circle-fill" style="color:var(--primary)"></i> Jumlah ${nama} bertambah`);
        } else {
            showToast(`<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning)"></i> Stok ${nama} tidak cukup`, true);
        }
    } else {
        if (stok <= 0) {
            showToast(`<i class="bi bi-x-circle-fill" style="color:var(--danger)"></i> Stok ${nama} habis`, true);
            return;
        }
        cart.push({ id, nama, tipe, harga, gambar, stok, jumlah: 1 });
        showToast(`<i class="bi bi-check-circle-fill" style="color:var(--success)"></i> ${nama} ditambahkan ke keranjang`);
    }
    simpanCart();
}

function ubahJumlah(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.jumlah += delta;
    if (item.jumlah <= 0) {
        hapusItem(id);
        return;
    }
    if (item.jumlah > item.stok) {
        item.jumlah = item.stok;
        showToast(`<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning)"></i> Melebihi stok tersedia`, true);
    }
    simpanCart();
}

function hapusItem(id) {
    cart = cart.filter(i => i.id !== id);
    simpanCart();
}

function kosongkanKeranjang() {
    if (confirm('Yakin kosongkan seluruh keranjang?')) {
        cart = [];
        simpanCart();
        tutupModalCheckout();
    }
}

function hitungTotal() {
    return cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
}

function hitungTotalQty() {
    return cart.reduce((sum, item) => sum + item.jumlah, 0);
}

function updateCartBadge() {
    const badge = document.getElementById('cartBadge');
    const qty = hitungTotalQty();
    badge.textContent = qty;
    badge.classList.toggle('hidden', qty === 0);
}

// ============================================================
// RENDER CART PANEL
// ============================================================
function renderCart() {
    const wrapper = document.getElementById('cartItemsWrapper');
    const emptyState = document.getElementById('cartEmptyState');
    const btnCheckout = document.getElementById('btnCheckout');
    const btnClear = document.getElementById('btnClearCart');
    const countEl = document.getElementById('cartItemCount');
    const totalEl = document.getElementById('cartTotal');

    const qty = hitungTotalQty();
    const total = hitungTotal();

    totalEl.textContent = formatRupiah(total);
    countEl.textContent = qty + ' item';

    btnCheckout.disabled = qty === 0;
    btnClear.style.display = qty > 0 ? 'flex' : 'none';

    // Remove existing items (keep empty state)
    const existingItems = wrapper.querySelectorAll('.cart-item');
    existingItems.forEach(el => el.remove());

    if (qty === 0) {
        emptyState.style.display = 'flex';
        return;
    }

    emptyState.style.display = 'none';

    cart.forEach(item => {
        const imgSrc = item.gambar
            ? `assets/uploads/${item.gambar}`
            : '';

        const el = document.createElement('div');
        el.className = 'cart-item';
        el.id = 'cartItem-' + item.id;
        el.innerHTML = `
            <div class="cart-item-img">
                ${imgSrc
                    ? `<img src="${imgSrc}" alt="${escapeHtml(item.nama)}" onerror="this.parentElement.innerHTML='<i class=\\'bi bi-bag\\' style=\\'font-size:1.4rem;color:var(--text-muted)\\'></i>'">`
                    : '<i class="bi bi-bag" style="font-size:1.4rem;color:var(--text-muted)"></i>'
                }
            </div>
            <div class="cart-item-detail">
                <div class="cart-item-name">${escapeHtml(item.nama)}</div>
                <div class="cart-item-type">${escapeHtml(item.tipe || '')}</div>
                <div class="cart-item-price">${formatRupiah(item.harga)}</div>
                <div class="cart-qty-control">
                    <button class="btn-qty" onclick="ubahJumlah(${item.id}, -1)" aria-label="Kurangi">−</button>
                    <span class="qty-display">${item.jumlah}</span>
                    <button class="btn-qty" onclick="ubahJumlah(${item.id}, 1)" aria-label="Tambah">+</button>
                    <span style="font-size:0.8rem; color:var(--text-muted); margin-left:4px;">
                        = ${formatRupiah(item.harga * item.jumlah)}
                    </span>
                </div>
            </div>
            <button class="btn-remove-item" onclick="hapusItem(${item.id})" aria-label="Hapus item">
                <i class="bi bi-trash3"></i>
            </button>
        `;
        wrapper.appendChild(el);
    });
}

// ============================================================
// TOGGLE CART
// ============================================================
function toggleCart() {
    const panel = document.getElementById('cartPanel');
    const overlay = document.getElementById('cartOverlay');
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = panel.classList.contains('active') ? 'hidden' : '';
}

// ============================================================
// CHECKOUT MODAL
// ============================================================
function bukaModalCheckout() {
    if (cart.length === 0) return;
    const modal = document.getElementById('checkoutModal');
    const container = document.getElementById('orderSummaryContainer');
    const modalTotal = document.getElementById('modalTotal');
    const inputBayar = document.getElementById('inputBayar');

    const total = hitungTotal();
    modalTotal.textContent = formatRupiah(total);
    inputBayar.value = '';
    document.getElementById('kembalianDisplay').style.display = 'none';

    // Build order summary table
    let tableHTML = `
        <table class="order-summary-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
    `;
    cart.forEach(item => {
        tableHTML += `
            <tr>
                <td>${escapeHtml(item.nama)}<br><span style="color:var(--text-muted);font-size:0.75rem">${escapeHtml(item.tipe||'')}</span></td>
                <td style="text-align:center;">${item.jumlah}</td>
                <td style="text-align:right;font-weight:700;">${formatRupiah(item.harga * item.jumlah)}</td>
            </tr>
        `;
    });
    tableHTML += '</tbody></table>';
    container.innerHTML = tableHTML;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Set default jumlah bayar = total
    setTimeout(() => { inputBayar.focus(); inputBayar.value = total; hitungKembalian(); }, 100);
}

function tutupModalCheckout() {
    document.getElementById('checkoutModal').classList.remove('active');
    document.body.style.overflow = '';
}

function hitungKembalian() {
    const total = hitungTotal();
    const bayar = parseInt(document.getElementById('inputBayar').value) || 0;
    const kembalian = bayar - total;
    const display = document.getElementById('kembalianDisplay');
    const valueEl = document.getElementById('kembalianValue');
    const btnProses = document.getElementById('btnProsesTransaksi');

    if (bayar > 0) {
        display.style.display = 'flex';
        if (kembalian >= 0) {
            valueEl.textContent = formatRupiah(kembalian);
            display.style.background = 'var(--success-light)';
            valueEl.style.color = 'var(--success)';
            btnProses.disabled = false;
        } else {
            valueEl.textContent = 'Kurang ' + formatRupiah(Math.abs(kembalian));
            display.style.background = 'var(--danger-light)';
            valueEl.style.color = 'var(--danger)';
            btnProses.disabled = true;
        }
    } else {
        display.style.display = 'none';
        btnProses.disabled = true;
    }
}

// ============================================================
// PROSES TRANSAKSI (AJAX ke checkout.php)
// ============================================================
function prosesTransaksi() {
    const bayar = parseInt(document.getElementById('inputBayar').value) || 0;
    const metode = document.getElementById('metodeBayar').value;
    const total = hitungTotal();

    if (bayar < total) {
        showToast('<i class="bi bi-exclamation-triangle-fill"></i> Jumlah bayar kurang!', true);
        return;
    }

    const btn = document.getElementById('btnProsesTransaksi');
    btn.innerHTML = '<span class="loading-spinner"></span> Memproses...';
    btn.disabled = true;

    const payload = {
        cart: cart,
        total_bayar: bayar,
        metode_bayar: metode
    };

    fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            cart = [];
            simpanCart();
            tutupModalCheckout();
            document.getElementById('cartPanel').classList.remove('active');
            document.getElementById('cartOverlay').classList.remove('active');
            document.body.style.overflow = '';
            // Redirect ke halaman bukti transaksi
            window.location.href = 'transaksi_sukses.php?kode=' + data.kode_transaksi;
        } else {
            showToast('<i class="bi bi-x-circle-fill"></i> ' + (data.message || 'Terjadi kesalahan'), true);
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Selesaikan Transaksi';
            btn.disabled = false;
        }
    })
    .catch(err => {
        showToast('<i class="bi bi-wifi-off"></i> Gagal terhubung ke server', true);
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Selesaikan Transaksi';
        btn.disabled = false;
        console.error(err);
    });
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function formatRupiah(angka) {
    return 'Rp ' + parseInt(angka).toLocaleString('id-ID');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function showToast(html, isError = false) {
    // Remove existing toasts
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());

    const toast = document.createElement('div');
    toast.className = 'toast-notification' + (isError ? ' error' : '');
    toast.innerHTML = html;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2800);
}

// ============================================================
// SCROLL TO TOP
// ============================================================
window.addEventListener('scroll', () => {
    const btn = document.getElementById('scrollTopBtn');
    btn.classList.toggle('visible', window.scrollY > 300);
});

// ============================================================
// SEARCH DEBOUNCE
// ============================================================
let searchTimeout;
document.getElementById('searchBar')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        this.form.submit();
    }, 600);
});

// Init on load
renderCart();
</script>

</body>
</html>
