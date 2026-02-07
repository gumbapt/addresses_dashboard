<?php
/**
 * Script para processar reports pendentes de um domínio.
 * Uso: php process-pending-reports.php [domain_id]
 * Sem argumento: processa domain 2 (zip.50g.io)
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$domainId = (int) ($argv[1] ?? 2);

$reports = \App\Models\Report::where('domain_id', $domainId)
    ->where('status', 'pending')
    ->orderBy('report_date')
    ->get();

$total = $reports->count();
echo "Processando {$total} reports pendentes do domínio {$domainId}...\n";

$processor = app(\App\Application\Services\ReportProcessor::class);
$success = 0;
$errors = 0;

foreach ($reports as $index => $report) {
    try {
        $processor->process($report->id, $report->raw_data ?? []);
        $report->update(['status' => 'processed']);
        $success++;
        if (($index + 1) % 25 == 0 || $index == 0 || $index == $total - 1) {
            echo sprintf("  %d/%d (%.1f%%)\n", $index + 1, $total, (($index + 1) / $total) * 100);
        }
    } catch (\Exception $e) {
        $errors++;
        echo "  ❌ Report {$report->id}: {$e->getMessage()}\n";
    }
}

echo "\n✅ Sucesso: {$success}\n";
echo ($errors > 0 ? "❌ Erros: {$errors}\n" : "");
