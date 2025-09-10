<?php
/**
 *  File name : transactionMail.php
 *  Author    : You
 *  Desc      : Template e-mail ringkasan transaksi (pinjam, pengembalian, perpanjangan).
 *  Plugin Name: Circulation Email Notifier
 *  Plugin URI: https://github.com/erwansetyobudi/Circulation-Email-Notifier
 *  Version: 1.0.0
 *  Author: Erwan Setyo Budi
 *  Author URI: https://github.com/erwansetyobudi/
 */

use SLiMS\Mail\TemplateContract;

class transactionMail extends TemplateContract
{
    protected $contents; // ← sudah ada
    private $member;
    private $receipt = [];

    public function __construct($memberObject)
    {
        $this->member = $memberObject;
    }

    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Isi data receipt dari $_SESSION['receipt_record']
     */
    public function setReceiptData(array $receipt)
    {
        $this->receipt = $receipt;
        return $this;
    }

    /**
     * Bangun satu blok tabel item
     */
    private function buildTableBlock(array $rows, array $mapping, $title)
    {
        if (empty($rows)) { return ''; }

        $trs = '';
        foreach ($rows as $r) {
            // dukungan field umum: title, classification, itemCode, loanDate, dueDate, returnDate, overdues
            $titleText  = $r['title'] ?? ($r['biblio_title'] ?? '');
            $itemCode   = $r['itemCode'] ?? ($r['item_code'] ?? '');
            $loanDate   = $r['loanDate'] ?? ($r['loan_date'] ?? '');
            $dueDate    = $r['dueDate'] ?? ($r['due_date'] ?? '');
            $returnDate = $r['returnDate'] ?? ($r['return_date'] ?? '');
            $overdueStr = '';
            if (!empty($r['overdues']) && is_array($r['overdues'])) {
                $days = $r['overdues']['days'] ?? '';
                $val  = $r['overdues']['value'] ?? '';
                if ($days || $val) {
                    $overdueStr = '<span style="display:block;margin-top:.2em">'.__('Overdue').": <strong>{$days}</strong> — <strong>{$val}</strong></span>";
                }
            }

            // Cover (jika punya URL cover pada data Anda, bisa ditambahkan)
            $coverImg = '';
            if (!empty($r['image'])) {
                $coverImg = '<img style="width: 90px; margin-right: 1em; border-radius: 5px;" src="'.htmlspecialchars($r['image']).'">';
            }

            $meta = [];
            if ($itemCode)   { $meta[] = 'Item Code : '.$itemCode; }
            if ($loanDate)   { $meta[] = 'Loan Date : '.$loanDate; }
            if ($dueDate)    { $meta[] = 'Due Date : '.$dueDate; }
            if ($returnDate) { $meta[] = 'Return Date : '.$returnDate; }

            $trs .= <<<HTML
            <tr>
                <td style="vertical-align:top;padding:8px 12px 8px 0">{$coverImg}</td>
                <td style="vertical-align:top;padding:8px 0">
                    <div style="font-weight:600;margin-bottom:.2em">{$titleText}</div>
                    <div style="font-size:13px;line-height:1.45">
                        <span style="display:block">{$mapping['member_label']}</span>
                        <span style="display:block">{$mapping['date_label']}</span>
                        <span style="display:block">{$mapping['extra_label']}</span>
                        <span style="display:block;margin-top:.3em">{$this->buildMeta($meta)}</span>
                        {$overdueStr}
                    </div>
                </td>
            </tr>
            HTML;
        }

        return <<<HTML
        <h3 style="margin:1.2em 0 .4em 0">{$title}</h3>
        <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">{$trs}</table>
        HTML;
    }

    private function buildMeta(array $lines)
    {
        $buff = '';
        foreach ($lines as $l) {
            if ($l === '') continue;
            $buff .= '<span style="display:block">'.$l.'</span>';
        }
        return $buff;
    }

    public function render()
    {
        $libraryName = config('library_name');

        $greet = sprintf(
            __('To <strong><!--MEMBER_NAME--> (<!--MEMBER_ID-->)</strong>, below is your circulation transaction summary:')
        );

        $footer = __('<p>Thank you.</p>
        <strong><!--DATE--></strong><br />Library Management');

        // Mapping label per blok
        $mapLoan = [
            'member_label' => __('Member').': '.$this->member->member_name,
            'date_label'   => __('Transaction Date').': '.date('Y-m-d H:i:s'),
            'extra_label'  => __('Type').': '.__('Loan'),
        ];
        $mapReturn = [
            'member_label' => __('Member').': '.$this->member->member_name,
            'date_label'   => __('Transaction Date').': '.date('Y-m-d H:i:s'),
            'extra_label'  => __('Type').': '.__('Return'),
        ];
        $mapExtend = [
            'member_label' => __('Member').': '.$this->member->member_name,
            'date_label'   => __('Transaction Date').': '.date('Y-m-d H:i:s'),
            'extra_label'  => __('Type').': '.__('Extension'),
        ];

        $loanBlock   = $this->buildTableBlock($this->receipt['loan']   ?? [], $mapLoan,   __('New Loans'));
        $returnBlock = $this->buildTableBlock($this->receipt['return'] ?? [], $mapReturn, __('Returns'));
        $extendBlock = $this->buildTableBlock($this->receipt['extend'] ?? [], $mapExtend, __('Extensions'));

        // rakit HTML (tanpa wrapper khusus; jika Anda ingin "nebeng" notemplate_page_tpl.php, bisa disuntikkan di Mailer)
        $html = <<<HTML
        <div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,'Open Sans','Helvetica Neue',sans-serif; padding:24px;">
            <div style="margin-bottom:12px">
                <h2 style="margin:0">{$libraryName}</h2>
            </div>
            <p style="margin: 10px 0 18px 0;">{$greet}</p>
            {$loanBlock}
            {$returnBlock}
            {$extendBlock}
            <div style="margin-top:18px">{$footer}</div>
        </div>
        HTML;

        $this->contents = str_ireplace(
            ['<!--MEMBER_ID-->', '<!--MEMBER_NAME-->', '<!--DATE-->'],
            [$this->member->member_id, $this->member->member_name, date('Y-m-d H:i:s')],
            $html
        );

        return $this;
    }
}
