<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\SavingsLedgerService;
use App\Support\XlsxWorkbookWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminSavingsController extends Controller
{
    public function show(Request $request, Customer $customer, SavingsLedgerService $ledger): JsonResponse
    {
        $filter = $request->validate([
            'category' => ['nullable', 'in:HISTORY,POKOK,WAJIB,SUKARELA'],
        ])['category'] ?? 'HISTORY';

        $latestTransaction = $customer->latestSavingsTransaction;
        $transactionQuery = $customer->savingsTransactions()
            ->with('recordedBy:id,name')
            ->latest('id');

        if ($filter !== 'HISTORY') {
            $transactionQuery->where('category', $filter);
        }

        $transactions = $transactionQuery->paginate(20)->withQueryString();
        if ($filter !== 'HISTORY') {
            $categoryBalances = $ledger->categoryBalancesForDescendingPage($customer, $filter, $transactions->getCollection());
            $transactions->getCollection()->each(function ($transaction) use ($categoryBalances): void {
                $transaction->setAttribute('category_balance_after', $categoryBalances[$transaction->id] ?? '0.00');
            });
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'member_code' => $customer->member_code,
                'name' => $customer->name,
                'status' => $customer->status,
            ],
            'balance' => $latestTransaction?->balance_after ?? '0.00',
            'category_filter' => $filter,
            'category_totals' => $ledger->categoryTotals($customer),
            'filtered_transaction_count' => $transactions->total(),
            'uncategorized_transactions' => $customer->savingsTransactions()->whereNull('category')->count(),
            'transactions' => $transactions,
        ]);
    }

    public function transactionIds(Request $request, Customer $customer): JsonResponse
    {
        $filter = $request->validate([
            'category' => ['nullable', 'in:HISTORY,POKOK,WAJIB,SUKARELA'],
        ])['category'] ?? 'HISTORY';

        $query = $customer->savingsTransactions()->orderBy('id');
        if ($filter !== 'HISTORY') {
            $query->where('category', $filter);
        }

        return response()->json(['ids' => $query->pluck('id')]);
    }

    public function update(Request $request, Customer $customer, int $transactionId, SavingsLedgerService $ledger): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:DEPOSIT,WITHDRAWAL'],
            'category' => ['required', 'in:POKOK,WAJIB,SUKARELA'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = $ledger->update(
            $customer,
            $transactionId,
            $request->user(),
            $data['type'],
            $data['category'],
            (string) $data['amount'],
            $data['note'] ?? null,
        );
        $customer->unsetRelation('latestSavingsTransaction');

        return response()->json([
            'transaction' => $transaction->load('recordedBy:id,name'),
            'balance' => $customer->latestSavingsTransaction?->balance_after ?? '0.00',
            'category_totals' => $ledger->categoryTotals($customer),
        ]);
    }

    public function destroy(Request $request, Customer $customer, int $transactionId, SavingsLedgerService $ledger): JsonResponse
    {
        $deletedCount = $ledger->deleteMany($customer, [$transactionId], $request->user());

        return $this->deletionResponse($customer, $ledger, $deletedCount);
    }

    public function destroySelected(Request $request, Customer $customer, SavingsLedgerService $ledger): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:5000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $deletedCount = $ledger->deleteMany($customer, array_map('intval', $data['ids']), $request->user());

        return $this->deletionResponse($customer, $ledger, $deletedCount);
    }

    private function deletionResponse(Customer $customer, SavingsLedgerService $ledger, int $deletedCount): JsonResponse
    {
        $customer->unsetRelation('latestSavingsTransaction');

        return response()->json([
            'deleted_count' => $deletedCount,
            'balance' => $customer->latestSavingsTransaction?->balance_after ?? '0.00',
            'category_totals' => $ledger->categoryTotals($customer),
        ]);
    }

    public function export(Request $request, Customer $customer, SavingsLedgerService $ledger, XlsxWorkbookWriter $workbookWriter): BinaryFileResponse
    {
        $filter = $request->validate([
            'category' => ['nullable', 'in:HISTORY,POKOK,WAJIB,SUKARELA'],
        ])['category'] ?? 'HISTORY';

        $categoryNames = [
            'POKOK' => 'Pokok',
            'WAJIB' => 'Wajib',
            'SUKARELA' => 'Sukarela',
        ];
        $categoryLabel = $filter === 'HISTORY' ? 'Semua jenis' : $categoryNames[$filter];
        $safeMemberCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $customer->member_code);
        $safeMemberCode = trim((string) $safeMemberCode, '-') ?: (string) $customer->id;
        $exportedAt = now();
        $filename = sprintf('riwayat-tabungan-%s-%s.xlsx', $safeMemberCode, $exportedAt->format('Ymd-His'));

        $balance = $customer->latestSavingsTransaction?->balance_after ?? '0.00';
        $categoryTotals = $ledger->categoryTotals($customer);
        $transactionQuery = $customer->savingsTransactions()->with('recordedBy:id,name')->orderBy('id');

        if ($filter !== 'HISTORY') {
            $transactionQuery->where('category', $filter);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'koperasi-savings-');
        $sheet = fopen('php://temp/maxmemory:5242880', 'w+b');
        if ($temporaryPath === false || $sheet === false) {
            if (is_resource($sheet)) {
                fclose($sheet);
            }
            if ($temporaryPath !== false) {
                @unlink($temporaryPath);
            }

            throw new \RuntimeException('File laporan Excel tidak dapat dibuat.');
        }

        $escapeXml = static function (?string $value): string {
            $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value ?? '') ?? '';

            return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };
        $writeTextCell = static function (string $address, ?string $value, int $style = 7) use ($sheet, $escapeXml): void {
            fwrite($sheet, '<c r="'.$address.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$escapeXml($value).'</t></is></c>');
        };
        $writeNumberCell = static function (string $address, ?string $value, int $style = 9) use ($sheet): void {
            $number = number_format((float) ($value ?? 0), 2, '.', '');
            fwrite($sheet, '<c r="'.$address.'" s="'.$style.'"><v>'.$number.'</v></c>');
        };
        $writeRow = static function (int $number, array $cells, ?int $height = null) use ($sheet): void {
            fwrite($sheet, '<row r="'.$number.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>');
            foreach ($cells as $cell) {
                $cell();
            }
            fwrite($sheet, '</row>');
        };

        try {
            fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane ySplit="10" topLeftCell="A11" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A11" sqref="A11"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="21"/><cols><col min="1" max="1" width="20" customWidth="1"/><col min="2" max="2" width="25" customWidth="1"/><col min="3" max="3" width="18" customWidth="1"/><col min="4" max="4" width="23" customWidth="1"/><col min="5" max="5" width="20" customWidth="1"/><col min="6" max="6" width="28" customWidth="1"/><col min="7" max="7" width="42" customWidth="1"/><col min="8" max="8" width="25" customWidth="1"/></cols><sheetData>');

            $writeRow(1, [static fn () => $writeTextCell('A1', 'LAPORAN RIWAYAT TABUNGAN ANGGOTA', 1)], 34);
            $writeRow(2, [static fn () => $writeTextCell('A2', 'Filter laporan: '.$categoryLabel.'  |  Dibuat: '.$exportedAt->format('d/m/Y H:i'), 2)], 24);
            $writeRow(3, []);
            $writeRow(4, [
                static fn () => $writeTextCell('A4', 'Nama anggota', 3),
                static fn () => $writeTextCell('B4', $customer->name, 4),
                static fn () => $writeTextCell('E4', 'Nomor anggota', 3),
                static fn () => $writeTextCell('F4', $customer->member_code, 4),
            ]);
            $writeRow(5, [
                static fn () => $writeTextCell('A5', 'Status', 3),
                static fn () => $writeTextCell('B5', $customer->status, 4),
                static fn () => $writeTextCell('E5', 'Tanggal ekspor', 3),
                static fn () => $writeTextCell('F5', $exportedAt->format('d/m/Y H:i:s'), 4),
            ]);
            $writeRow(6, []);
            $writeRow(7, [
                static fn () => $writeTextCell('A7', 'Saldo keseluruhan', 3),
                static fn () => $writeNumberCell('B7', $balance, 5),
                static fn () => $writeTextCell('D7', 'Tabungan Pokok', 3),
                static fn () => $writeNumberCell('E7', $categoryTotals['POKOK'] ?? '0.00', 5),
                static fn () => $writeTextCell('F7', 'Tabungan Wajib', 3),
                static fn () => $writeNumberCell('G7', $categoryTotals['WAJIB'] ?? '0.00', 5),
            ]);
            $writeRow(8, [
                static fn () => $writeTextCell('D8', 'Tabungan Sukarela', 3),
                static fn () => $writeNumberCell('E8', $categoryTotals['SUKARELA'] ?? '0.00', 5),
            ]);
            $writeRow(9, []);

            $balanceHeader = $filter === 'HISTORY'
                ? 'Saldo keseluruhan setelah transaksi (Rp)'
                : 'Saldo '.$categoryLabel.' setelah transaksi (Rp)';
            $headers = ['Tanggal', 'Nomor bukti', 'Transaksi', 'Jenis tabungan', 'Nominal (Rp)', $balanceHeader, 'Catatan', 'Dicatat oleh'];
            $headerCells = [];
            foreach ($headers as $index => $header) {
                $column = chr(ord('A') + $index);
                $headerCells[] = static fn () => $writeTextCell($column.'10', $header, 6);
            }
            $writeRow(10, $headerCells, 34);

            $rowNumber = 11;
            $categoryBalanceMinor = 0;
            $transactionQuery->chunk(500, function ($transactions) use (&$rowNumber, &$categoryBalanceMinor, $writeRow, $writeTextCell, $writeNumberCell, $categoryNames, $sheet, $filter, $ledger): void {
                foreach ($transactions as $transaction) {
                    $row = $rowNumber;
                    $alternate = $row % 2 === 0;
                    $textStyle = $alternate ? 8 : 7;
                    $numberStyle = $alternate ? 10 : 9;
                    $dateStyle = $alternate ? 12 : 11;
                    $displayedBalance = $transaction->balance_after;
                    if ($filter !== 'HISTORY') {
                        $amountMinor = $ledger->toMinor((string) $transaction->amount);
                        $categoryBalanceMinor += $transaction->type === 'DEPOSIT' ? $amountMinor : -$amountMinor;
                        $displayedBalance = $ledger->fromMinor($categoryBalanceMinor);
                    }
                    $date = $transaction->created_at
                        ? (new \DateTimeImmutable($transaction->created_at->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')))->getTimestamp() / 86400 + 25569
                        : null;

                    $writeRow($row, [
                        static function () use ($date, $row, $sheet, $dateStyle): void {
                            if ($date === null) {
                                return;
                            }
                            fwrite($sheet, '<c r="A'.$row.'" s="'.$dateStyle.'"><v>'.number_format($date, 10, '.', '').'</v></c>');
                        },
                        static fn () => $writeTextCell('B'.$row, $transaction->reference_code, $textStyle),
                        static fn () => $writeTextCell('C'.$row, $transaction->type === 'DEPOSIT' ? 'Setoran' : 'Penarikan', $textStyle),
                        static fn () => $writeTextCell('D'.$row, $transaction->category ? ($categoryNames[$transaction->category] ?? $transaction->category) : 'Belum dikategorikan', $textStyle),
                        static fn () => $writeNumberCell('E'.$row, $transaction->amount, $numberStyle),
                        static fn () => $writeNumberCell('F'.$row, $displayedBalance, $numberStyle),
                        static fn () => $writeTextCell('G'.$row, $transaction->note, $textStyle),
                        static fn () => $writeTextCell('H'.$row, $transaction->recordedBy?->name ?? 'Admin', $textStyle),
                    ]);
                    $rowNumber++;
                }
            });

            $lastRow = max(10, $rowNumber - 1);
            fwrite($sheet, '</sheetData><autoFilter ref="A10:H'.$lastRow.'"/><mergeCells count="2"><mergeCell ref="A1:H1"/><mergeCell ref="A2:H2"/></mergeCells><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/></worksheet>');
            rewind($sheet);

            $styles = <<<'XML'
    <?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0.00;[Red]&quot;Rp&quot; -#,##0.00"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy hh:mm"/></numFmts><fonts count="4"><font><sz val="10"/><color rgb="FF1C2D35"/><name val="Aptos"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Aptos"/></font><font><b/><sz val="10"/><color rgb="FF063C55"/><name val="Aptos"/></font></fonts><fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF063C55"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0B526E"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF4F7"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF4F8FA"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD3DEE3"/></left><right style="thin"><color rgb="FFD3DEE3"/></right><top style="thin"><color rgb="FFD3DEE3"/></top><bottom style="thin"><color rgb="FFD3DEE3"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="13"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="4" borderId="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="164" fontId="3" fillId="4" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="5" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="164" fontId="0" fillId="5" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf><xf numFmtId="165" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="165" fontId="0" fillId="5" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>
    XML;
            $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
            $rootRelationships = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
            $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Riwayat Tabungan" sheetId="1" r:id="rId1"/></sheets></workbook>';
            $workbookRelationships = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

            $workbookWriter->write($temporaryPath, [
                '[Content_Types].xml' => $contentTypes,
                '_rels/.rels' => $rootRelationships,
                'xl/workbook.xml' => $workbook,
                'xl/_rels/workbook.xml.rels' => $workbookRelationships,
                'xl/styles.xml' => $styles,
                'xl/worksheets/sheet1.xml' => $sheet,
            ]);
        } catch (\Throwable $exception) {
            if (is_resource($sheet)) {
                fclose($sheet);
            }
            @unlink($temporaryPath);
            throw $exception;
        }
        fclose($sheet);

        return response()->download($temporaryPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    public function store(Request $request, Customer $customer, SavingsLedgerService $ledger): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:DEPOSIT,WITHDRAWAL'],
            'category' => ['required', 'in:POKOK,WAJIB,SUKARELA'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'idempotency_key' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = $ledger->record(
            $customer,
            $request->user(),
            $data['type'],
            $data['category'],
            (string) $data['amount'],
            $data['idempotency_key'],
            $data['note'] ?? null,
        );

        return response()->json([
            'transaction' => $transaction->load('recordedBy:id,name'),
            'balance' => $transaction->balance_after,
        ], $transaction->wasRecentlyCreated ? 201 : 200);
    }
}
