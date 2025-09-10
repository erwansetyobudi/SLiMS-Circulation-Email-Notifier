<?php
/**
 * Plugin Name: Circulation Email Notifier
 * Plugin URI: https://github.com/erwansetyobudi/Circulation-Email-Notifier
 * Description: Mengirim email otomatis ke anggota setiap transaksi sirkulasi selesai (pinjam/pengembalian/perpanjangan) saat tombol Finish Transaction.
 * Version: 1.0.0
 * Author: Erwan Setyo Budi
 * Author URI: https://github.com/erwansetyobudi/
 */

if (!defined('INDEX_AUTH')) { die('No direct access'); }

use SLiMS\Plugins;

// Ambil instance plugin
$plugin = Plugins::getInstance();

/**
 * Cara termudah: cukup load bootstrap (index.php) agar hook terdaftar.
 * Di index.php kita sudah memanggil:
 *   Plugins::getInstance()->register(Plugins::CIRCULATION_AFTER_SUCCESSFUL_TRANSACTION, function (...) { ... });
 *
 * Jadi di sini cukup require_once.
 */
require_once __DIR__ . '/index.php';

/**
 * (Opsional) Jika ingin ada halaman pengaturan/tombol test kirim email,
 * bisa daftarkan menu admin seperti ini:
 *
 * $plugin->registerMenu('system', 'Circulation Email Notifier', __DIR__ . '/settings.php');
 *
 * Lalu Anda buat file settings.php sesuai kebutuhan.
 */
