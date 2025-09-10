<?php
/**
 * Plugin Name: Circulation Email Notifier
 * Plugin URI: https://github.com/erwansetyobudi/Circulation-Email-Notifier
 * Description: Mengirim email otomatis ke anggota setiap transaksi sirkulasi selesai (pinjam/pengembalian/perpanjangan) saat tombol Finish Transaction.
 * Version: 1.0.0
 * Author: Erwan Setyo Budi
 * Author URI: https://github.com/erwansetyobudi/
 */

use SLiMS\Plugins;
use SLiMS\DB;

if (!defined('INDEX_AUTH')) { die('No direct access'); }

/**
 * Helper logging ke file debug.log
 */
if (!function_exists('circ_notify_log')) {
  function circ_notify_log($msg) {
    @file_put_contents(__DIR__.'/debug.log', '['.date('Y-m-d H:i:s')."] ".$msg.PHP_EOL, FILE_APPEND);
  }
}

/**
 * Helper untuk munculkan popup toastr dari plugin
 */
if (!function_exists('circ_notify_toastr')) {
  function circ_notify_toastr($msg, $type = 'success') {
    // kalau helper toastr() ada
    if (function_exists('toastr')) {
        toastr($msg)->$type();
    } else {
        // fallback echo JS agar tampil di parent admin page
        echo "<script>parent.toastr && parent.toastr.{$type}('".addslashes($msg)."');</script>";
    }
  }
}

/**
 * Hook circulation after successful transaction
 */
Plugins::getInstance()->register(Plugins::CIRCULATION_AFTER_SUCCESSFUL_TRANSACTION, function ($payload) {
    circ_notify_log('HOOK FIRED type='.gettype($payload));

    // --- Normalisasi payload ---
    $data = null;
    if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
        $data = $payload['data'];
        circ_notify_log('Payload shape: with data key');
    } elseif (is_array($payload) && (isset($payload['memberID']) || isset($payload['loan']) || isset($payload['return']) || isset($payload['extend']))) {
        $data = $payload;
        circ_notify_log('Payload shape: raw receipt array');
    } elseif (isset($_SESSION['receipt_record']) && is_array($_SESSION['receipt_record'])) {
        $data = $_SESSION['receipt_record'];
        circ_notify_log('Payload shape: fallback from $_SESSION');
    } else {
        circ_notify_log('No usable payload; abort');
        return;
    }

    // Pastikan ada data transaksi
    $hasLoan   = !empty($data['loan']);
    $hasReturn = !empty($data['return']);
    $hasExtend = !empty($data['extend']);
    circ_notify_log("Blocks: loan=".($hasLoan?1:0)." return=".($hasReturn?1:0)." extend=".($hasExtend?1:0));
    if (!$hasLoan && !$hasReturn && !$hasExtend) {
        circ_notify_log('Nothing to send');
        return;
    }

    // Ambil email anggota
    $memberID = $data['memberID'] ?? null;
    if (!$memberID) { circ_notify_log('No memberID in data'); return; }

    $db = DB::getInstance('mysqli');
    $q  = $db->query("SELECT member_id, member_name, member_email FROM member WHERE member_id='".$db->escape_string($memberID)."' LIMIT 1");
    if (!$q || $q->num_rows < 1) { circ_notify_log('Member not found: '.$memberID); return; }
    $member = (object)$q->fetch_assoc();
    if (empty($member->member_email)) { circ_notify_log('Empty member email'); return; }
    circ_notify_log('Member email: '.$member->member_email);

    // Build isi email
    require_once __DIR__.'/transactionMail.php';
    try {
        $tpl = new transactionMail($member);
        $tpl->setReceiptData($data)->render();
    } catch (\Throwable $e) {
        circ_notify_log('Template error: '.$e->getMessage());
        return;
    }

    // Subject
    $subjectBits = [];
    if ($hasLoan)   { $subjectBits[] = __('Loans'); }
    if ($hasReturn) { $subjectBits[] = __('Returns'); }
    if ($hasExtend) { $subjectBits[] = __('Extensions'); }
    $subject = sprintf('[%s] %s — %s',
        config('library_name'),
        __('Circulation Transaction Summary'),
        implode(' + ', $subjectBits)
    );

    /**
     * 1. Kirim dengan SLiMS\Mail (utama)
     */
    try {
        if (class_exists('\SLiMS\Mail')) {
            \SLiMS\Mail::to($member->member_email, $member->member_name)
                ->subject($subject)
                ->message($tpl->getContents())
                ->send();

            circ_notify_log('Mail sent OK via \\SLiMS\\Mail');
            circ_notify_toastr('Email sudah terkirim ke '.$member->member_email, 'success');
            return;
        }
    } catch (\Throwable $e) {
        circ_notify_log('SLiMS\\Mail error: '.$e->getMessage());
        circ_notify_toastr('Gagal mengirim email ke '.$member->member_email, 'error');
    }

    /**
     * 2. Fallback ke PHPMailer dengan config/mail.php
     */
    try {
        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            $m = new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->isHTML(true);

            $mailConf = config('mail', []);
            $fromAddr = $mailConf['from']      ?? 'no-reply@example.com';
            $fromName = $mailConf['from_name'] ?? config('library_name', 'Library');

            // set SMTP dari konfigurasi
            if (!empty($mailConf['server']) && !empty($mailConf['server_port'])) {
                $m->isSMTP();
                $server = $mailConf['server'];
                if (strpos($server, '://') !== false) {
                    $server = preg_replace('#^.+://#', '', $server);
                    $server = preg_replace('#:\d+$#', '', $server);
                }
                $m->Host = $server;
                $m->Port = (int)($mailConf['server_port'] ?? 587);
                if (!empty($mailConf['auth_enable'])) {
                    $m->SMTPAuth = true;
                    $m->Username = $mailConf['auth_username'] ?? '';
                    $m->Password = $mailConf['auth_password'] ?? '';
                }
                if (!empty($mailConf['SMTPSecure'])) {
                    $m->SMTPSecure = $mailConf['SMTPSecure'];
                }
            }

            $m->setFrom($fromAddr, $fromName);
            if (!empty($mailConf['reply_to'])) {
                $m->addReplyTo($mailConf['reply_to'], $mailConf['reply_to_name'] ?? $fromName);
            }

            $m->addAddress($member->member_email, $member->member_name);
            $m->Subject = $subject;
            $m->Body = $tpl->getContents();

            $m->send();
            circ_notify_log('Mail sent OK via PHPMailer fallback');
            circ_notify_toastr('Email sudah terkirim ke '.$member->member_email, 'success');
            return;
        }
    } catch (\Throwable $e) {
        circ_notify_log('PHPMailer fallback error: '.$e->getMessage());
        circ_notify_toastr('Gagal mengirim email ke '.$member->member_email, 'error');
    }

    circ_notify_log('All mail methods failed.');
});
