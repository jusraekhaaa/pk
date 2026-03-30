<?php
/**
 * TOKO FHIKA - Halaman Produk per Merk
 * File: produk.php
 * Deskripsi: Menampilkan daftar produk dari merk yang dipilih (Gambar 2 referensi).
 */
require_once 'koneksi.php';
cekLogin();

// Validasi parameter brand_id
$brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
if ($brand_id <= 0) {
    redirect(BASE_URL . 'index.php');
}

// Ambil data merk
$stmt = $koneksi->prepare("SELECT id, nama, perusahaan, logo FROM brands WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $brand_id);
$stmt->execute();
$brand = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$brand) {
    redirect(BASE_URL . 'index.php');
}

// Filter kategori dari GET
$filter_category = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$search_query    = isset($_GET['q']) ? trim($_GET['q']) : '';

// Ambil semua kategori
$categories_all = $koneksi->query("SELECT id, nama FROM categories ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil produk dari brand ini
$where = "WHERE p.brand_id = $brand_id AND p.is_active = 1";
if ($filter_category > 0) {
    $where .= " AND p.category_id = $filter_category";
}
if ($search_query !== '') {
    $like = '%' . $koneksi->real_escape_string($search_query) . '%';
    $where .= " AND (p.nama LIKE '$like' OR p.tipe LIKE '$like')";
}

$sql_products = "
    SELECT
        p.id, p.kode_produk, p.nama, p.tipe, p.harga_jual,
        p.stok, p.stok_minimum, p.satuan, p.gambar,
        c.nama AS kategori_nama
    FROM products p
    JOIN categories c ON c.id = p.category_id
    $where
    ORDER BY p.nama ASC
";
$q_products = $koneksi->query($sql_products);
$products   = $q_products ? $q_products->fetch_all(MYSQLI_ASSOC) : [];

// Jumlah cart
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
    <title><?= htmlspecialchars($brand['nama']) ?> - <?= APP_NAME ?></title>
    <meta name="description" content="Produk <?= htmlspecialchars($brand['nama']) ?> dari <?= htmlspecialchars($brand['perusahaan']) ?> - Toko Fhika">
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
        <a href="index.php" class="navbar-brand" style="white-space:nowrap; margin-right:12px;">
            TOKO<span>FHIKA</span>
        </a>

        <!-- Search Products -->
        <form method="GET" action="" class="navbar-search-group" style="flex:1;">
            <input type="hidden" name="brand_id" value="<?= $brand_id ?>">
            <input
                type="text"
                class="form-control"
                name="q"
                id="searchBar"
                placeholder="Cari produk <?= htmlspecialchars($brand['nama']) ?>..."
                value="<?= htmlspecialchars($search_query) ?>"
                autocomplete="off"
            >
            <select class="form-select" name="category_id" id="categoryFilter" onchange="this.form.submit()">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories_all as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Right controls -->
        <div style="display:flex; align-items:center; gap:12px; margin-left:auto;">
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin/dashboard.php" style="font-size:0.82rem; font-weight:600; color:var(--primary); white-space:nowrap;">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            <?php endif; ?>
            <div style="width:1px; height:24px; background:var(--border-color);"></div>
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg,var(--primary),#7C3AED); display:flex; align-items:center; justify-content:center; color:white; font-size:0.75rem; font-weight:800;">
                    <?= strtoupper(substr($_SESSION['user_nama'], 0, 2)) ?>
                </div>
            </div>

            <button class="nav-cart-btn" id="btnOpenCart" onclick="toggleCart()">
                <i class="bi bi-bag-check"></i>
                <span>Keranjang</span>
                <span class="cart-badge <?= $cart_count === 0 ? 'hidden' : '' ?>" id="cartBadge">
                    <?= $cart_count ?>
                </span>
            </button>

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

    <!-- Page Header Bar -->
    <div class="page-header-bar">
        <div class="page-header-left">
            <!-- Tombol Kembali -->
            <a href="index.php" class="btn-kembali" id="btnKembali">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>

            <div style="width:1px; height:28px; background:var(--border-color);"></div>

            <!-- Brand Info -->
            <div>
                <div class="brand-page-title"><?= htmlspecialchars($brand['nama']) ?></div>
                <div class="brand-page-subtitle">
                    <i class="bi bi-building"></i>
                    <?= htmlspecialchars($brand['perusahaan']) ?>
                    &bull; <?= count($products) ?> produk ditampilkan
                </div>
            </div>
        </div>

        <!-- Sort / View options -->
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">Urutkan:</span>
            <select id="sortSelect" class="form-select" style="width:auto; min-width:150px; font-size:0.83rem; padding:6px 32px 6px 12px; border-radius:8px;" onchange="sortProducts(this.value)">
                <option value="default">Default</option>
                <option value="harga_asc">Harga: Terendah</option>
                <option value="harga_desc">Harga: Tertinggi</option>
                <option value="nama_asc">Nama: A–Z</option>
                <option value="stok_desc">Stok Terbanyak</option>
            </select>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
            <h2 class="empty-state-title">Tidak Ada Produk</h2>
            <p class="empty-state-text">Belum ada produk untuk merk <strong><?= htmlspecialchars($brand['nama']) ?></strong> yang sesuai filter ini.</p>
            <a href="produk.php?brand_id=<?= $brand_id ?>" class="btn-primary-sm" style="display:inline-flex; margin:0 auto;">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
            </a>
        </div>
    <?php else: ?>

        <!-- Product Grid -->
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $prod):
                $harga_fmt = formatRupiah($prod['harga_jual']);
                $stok_habis = $prod['stok'] <= 0;
                $stok_rendah = !$stok_habis && $prod['stok'] <= $prod['stok_minimum'];
                $img_src = !empty($prod['gambar']) && file_exists(UPLOAD_DIR . $prod['gambar'])
                           ? UPLOAD_URL . htmlspecialchars($prod['gambar'])
                           : '';
                // Encode data untuk JS
                $js_nama  = addslashes($prod['nama']);
                $js_tipe  = addslashes($prod['tipe'] ?? '');
                $js_gambar = addslashes($prod['gambar'] ?? '');
            ?>
            <div class="product-card"
                 data-id="<?= $prod['id'] ?>"
                 data-nama="<?= htmlspecialchars($prod['nama'], ENT_QUOTES) ?>"
                 data-harga="<?= $prod['harga_jual'] ?>"
                 data-stok="<?= $prod['stok'] ?>"
                 id="produk-<?= $prod['id'] ?>">

                <!-- Gambar Produk -->
                <div class="product-img-wrapper">
                    <?php if ($img_src): ?>
                        <img
                            src="<?= $img_src ?>"
                            alt="<?= htmlspecialchars($prod['nama']) ?>"
                            loading="lazy"
                            onerror="this.parentElement.innerHTML='<div class=\'product-img-placeholder\'><i class=\'bi bi-bag\' style=\'color:var(--primary);\'></i></div>'"
                        >
                    <?php else: ?>
                        <div class="product-img-placeholder">
                            <i class="bi bi-bag" style="color:var(--primary);"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Stok Badge -->
                    <?php if ($stok_habis): ?>
                        <span class="product-stok-badge habis">Stok Habis</span>
                    <?php elseif ($stok_rendah): ?>
                        <span class="product-stok-badge rendah">Stok Rendah</span>
                    <?php endif; ?>
                </div>

                <!-- Info Produk -->
                <div class="product-info">
                    <div class="product-name"><?= htmlspecialchars($prod['nama']) ?></div>
                    <div class="product-type"><?= htmlspecialchars($prod['tipe'] ?? '') ?></div>
                    <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                        <?= htmlspecialchars($prod['kategori_nama']) ?>
                    </div>
                    <div class="product-price"><?= $harga_fmt ?></div>
                    <div class="product-stok-info <?= $stok_rendah ? 'low' : '' ?>">
                        <i class="bi bi-box-seam"></i>
                        <?php if ($stok_habis): ?>
                            <span style="color:var(--danger); font-weight:600;">Habis</span>
                        <?php else: ?>
                            Stok: <?= $prod['stok'] ?> <?= htmlspecialchars($prod['satuan']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add to Cart Button -->
                <button
                    class="btn-add-cart"
                    id="btnCart-<?= $prod['id'] ?>"
                    onclick="klikTambahKeranjang(<?= $prod['id'] ?>, '<?= $js_nama ?>', '<?= $js_tipe ?>', <?= $prod['harga_jual'] ?>, '<?= $js_gambar ?>', <?= $prod['stok'] ?>)"
                    <?= $stok_habis ? 'disabled' : '' ?>
                    aria-label="Tambah <?= htmlspecialchars($prod['nama']) ?> ke keranjang"
                >
                    <?php if ($stok_habis): ?>
                        <i class="bi bi-x-circle"></i> Stok Habis
                    <?php else: ?>
                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                    <?php endif; ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>
<!-- END MAIN CONTENT -->

<!-- ============================================================
     CART SIDEBAR
     ============================================================ -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>

<div class="cart-panel" id="cartPanel">
    <div class="cart-panel-header">
        <div>
            <div class="cart-panel-title"><i class="bi bi-bag-check me-2"></i>Keranjang Belanja</div>
            <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;" id="cartItemCount">0 item</div>
        </div>
        <button class="btn-close-cart" onclick="toggleCart()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cart-items-wrapper" id="cartItemsWrapper">
        <div class="cart-empty-state" id="cartEmptyState">
            <i class="bi bi-bag-x"></i>
            <p>Keranjang masih kosong</p>
            <span style="font-size:0.82rem;">Klik tombol "Tambah ke Keranjang" di bawah produk</span>
        </div>
    </div>
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

<!-- Checkout Modal -->
<div class="modal-backdrop-custom" id="checkoutModal">
    <div class="modal-box">
        <div class="modal-header-custom">
            <h5><i class="bi bi-receipt me-2"></i>Konfirmasi Pembayaran</h5>
            <button class="btn-close-cart" onclick="tutupModalCheckout()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body-custom">
            <p style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">Ringkasan Pesanan</p>
            <div id="orderSummaryContainer"></div>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-top:2px solid var(--border-color); margin-top:8px;">
                <span style="font-size:1rem; font-weight:700;">TOTAL</span>
                <span style="font-size:1.2rem; font-weight:800; color:var(--primary);" id="modalTotal"></span>
            </div>
            <div class="payment-input-group" style="margin-top:16px;">
                <label class="payment-input-label" for="metodeBayar">Metode Pembayaran</label>
                <select id="metodeBayar" class="form-control-custom" style="margin-bottom:14px; cursor:pointer;">
                    <option value="tunai">💵 Tunai</option>
                    <option value="transfer">🏦 Transfer Bank</option>
                    <option value="qris">📱 QRIS</option>
                </select>
                <label class="payment-input-label" for="inputBayar">Jumlah Dibayar</label>
                <input type="number" id="inputBayar" class="payment-input" placeholder="0" min="0" oninput="hitungKembalian()">
                <div class="kembalian-display" id="kembalianDisplay" style="display:none;">
                    <span class="kembalian-label"><i class="bi bi-arrow-return-left"></i> Kembalian</span>
                    <span class="kembalian-value" id="kembalianValue">Rp 0</span>
                </div>
            </div>
        </div>
        <div class="modal-footer-custom">
            <button class="btn-secondary-custom" onclick="tutupModalCheckout()"><i class="bi bi-arrow-left"></i> Batal</button>
            <button class="btn-primary-custom" id="btnProsesTransaksi" onclick="prosesTransaksi()">
                <i class="bi bi-check2-circle"></i> Selesaikan Transaksi
            </button>
        </div>
    </div>
</div>

<button class="scroll-top-btn" id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <i class="bi bi-arrow-up"></i>
</button>

<script>
// ============================================================
// CART STATE
// ============================================================
let cart = JSON.parse(localStorage.getItem('tokofhika_cart') || '[]');

function simpanCart() {
    localStorage.setItem('tokofhika_cart', JSON.stringify(cart));
    renderCart();
    updateCartBadge();
}

// ============================================================
// TAMBAH KE KERANJANG — dengan animasi tombol
// ============================================================
function klikTambahKeranjang(id, nama, tipe, harga, gambar, stok) {
    tambahKeKeranjang(id, nama, tipe, harga, gambar, stok);

    // Animasi tombol
    const btn = document.getElementById('btnCart-' + id);
    if (btn) {
        btn.classList.add('added');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Ditambahkan!';
        setTimeout(() => {
            btn.classList.remove('added');
            btn.innerHTML = originalHTML;
        }, 1200);
    }
}

function tambahKeKeranjang(id, nama, tipe, harga, gambar, stok) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.jumlah < stok) {
            existing.jumlah++;
            showToast(`<i class="bi bi-plus-circle-fill" style="color:var(--primary)"></i> Jumlah ${nama} bertambah`);
        } else {
            showToast(`<i class="bi bi-exclamation-triangle-fill" style="color:var(--warning)"></i> Stok maksimal tercapai`, true);
        }
    } else {
        cart.push({ id, nama, tipe, harga, gambar, stok, jumlah: 1 });
        showToast(`<i class="bi bi-check-circle-fill" style="color:var(--success)"></i> ${nama} ditambahkan`);
    }
    simpanCart();
}

function ubahJumlah(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.jumlah += delta;
    if (item.jumlah <= 0) { hapusItem(id); return; }
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

    wrapper.querySelectorAll('.cart-item').forEach(el => el.remove());

    if (qty === 0) {
        emptyState.style.display = 'flex';
        return;
    }
    emptyState.style.display = 'none';

    cart.forEach(item => {
        const imgSrc = item.gambar ? `assets/uploads/${item.gambar}` : '';
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
                    <button class="btn-qty" onclick="ubahJumlah(${item.id}, -1)">−</button>
                    <span class="qty-display">${item.jumlah}</span>
                    <button class="btn-qty" onclick="ubahJumlah(${item.id}, 1)">+</button>
                    <span style="font-size:0.8rem; color:var(--text-muted); margin-left:4px;">= ${formatRupiah(item.harga * item.jumlah)}</span>
                </div>
            </div>
            <button class="btn-remove-item" onclick="hapusItem(${item.id})"><i class="bi bi-trash3"></i></button>
        `;
        wrapper.appendChild(el);
    });
}

function toggleCart() {
    const panel = document.getElementById('cartPanel');
    const overlay = document.getElementById('cartOverlay');
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = panel.classList.contains('active') ? 'hidden' : '';
}

function bukaModalCheckout() {
    if (cart.length === 0) return;
    const total = hitungTotal();
    document.getElementById('modalTotal').textContent = formatRupiah(total);
    document.getElementById('inputBayar').value = '';
    document.getElementById('kembalianDisplay').style.display = 'none';

    let tableHTML = `<table class="order-summary-table"><thead><tr><th>Produk</th><th style="text-align:center">Qty</th><th style="text-align:right">Subtotal</th></tr></thead><tbody>`;
    cart.forEach(item => {
        tableHTML += `<tr>
            <td>${escapeHtml(item.nama)}<br><span style="color:var(--text-muted);font-size:0.75rem">${escapeHtml(item.tipe||'')}</span></td>
            <td style="text-align:center">${item.jumlah}</td>
            <td style="text-align:right;font-weight:700">${formatRupiah(item.harga * item.jumlah)}</td>
        </tr>`;
    });
    tableHTML += '</tbody></table>';
    document.getElementById('orderSummaryContainer').innerHTML = tableHTML;

    document.getElementById('checkoutModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        document.getElementById('inputBayar').focus();
        document.getElementById('inputBayar').value = total;
        hitungKembalian();
    }, 100);
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

    fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart, total_bayar: bayar, metode_bayar: metode })
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
            window.location.href = 'transaksi_sukses.php?kode=' + data.kode_transaksi;
        } else {
            showToast('<i class="bi bi-x-circle-fill"></i> ' + (data.message || 'Terjadi kesalahan'), true);
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Selesaikan Transaksi';
            btn.disabled = false;
        }
    })
    .catch(() => {
        showToast('<i class="bi bi-wifi-off"></i> Gagal terhubung ke server', true);
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Selesaikan Transaksi';
        btn.disabled = false;
    });
}

// ============================================================
// SORT PRODUCTS (client-side)
// ============================================================
function sortProducts(mode) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.product-card'));

    cards.sort((a, b) => {
        switch(mode) {
            case 'harga_asc':
                return parseFloat(a.dataset.harga) - parseFloat(b.dataset.harga);
            case 'harga_desc':
                return parseFloat(b.dataset.harga) - parseFloat(a.dataset.harga);
            case 'nama_asc':
                return a.dataset.nama.localeCompare(b.dataset.nama, 'id');
            case 'stok_desc':
                return parseFloat(b.dataset.stok) - parseFloat(a.dataset.stok);
            default:
                return 0; // Keep original order
        }
    });

    cards.forEach(card => grid.appendChild(card));
}

// ============================================================
// HELPERS
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
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());
    const toast = document.createElement('div');
    toast.className = 'toast-notification' + (isError ? ' error' : '');
    toast.innerHTML = html;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2800);
}

window.addEventListener('scroll', () => {
    document.getElementById('scrollTopBtn').classList.toggle('visible', window.scrollY > 300);
});

// Debounce search
let searchTimeout;
document.getElementById('searchBar')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => this.form.submit(), 600);
});

// Init
renderCart();
</script>

</body>
</html>
