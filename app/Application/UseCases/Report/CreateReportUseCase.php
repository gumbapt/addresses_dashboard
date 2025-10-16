<?php

namespace App\Application\UseCases\Report;

use App\Domain\Entities\Report;
use App\Domain\Repositories\ReportRepositoryInterface;
use DateTime;

class CreateReportUseCase
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    public function execute(
        int $domainId,
        array $reportData
    ): Report {
        // Extract metadata
        $metadata = $reportData['metadata'];
        $reportDate = $metadata['report_date'];
        
        // Verificar se já existe um relatório para esta data+domínio
        $existingReport = \App\Models\Report::where('domain_id', $domainId)
            ->whereDate('report_date', $reportDate)
            ->first();
        
        if ($existingReport) {
            // ATUALIZAR o relatório existente ao invés de criar um novo
            $existingReport->update([
                'report_period_start' => new DateTime($metadata['report_period']['start']),
                'report_period_end' => new DateTime($metadata['report_period']['end']),
                'generated_at' => new DateTime($metadata['generated_at']),
                'total_processing_time' => $metadata['total_processing_time'] ?? 0,
                'data_version' => $metadata['data_version'],
                'raw_data' => $reportData,
                'status' => 'pending', // Reset para pending para reprocessar
            ]);
            
            // Limpar dados processados antigos para reprocessar
            \App\Models\ReportSummary::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportProvider::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportState::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportCity::where('report_id', $existingReport->id)->delete();
            \App\Models\ReportZipCode::where('report_id', $existingReport->id)->delete();
            
            return $existingReport->toEntity();
        }
        
        return $this->reportRepository->create(
            domainId: $domainId,
            reportDate: $reportDate,
            reportPeriodStart: new DateTime($metadata['report_period']['start']),
            reportPeriodEnd: new DateTime($metadata['report_period']['end']),
            generatedAt: new DateTime($metadata['generated_at']),
            totalProcessingTime: $metadata['total_processing_time'] ?? 0,
            dataVersion: $metadata['data_version'],
            rawData: $reportData,
            status: 'pending'
        );
    }

    public function executeWithStatus(
        int $domainId,
        array $reportData,
        string $status
    ): Report {
        $metadata = $reportData['metadata'];
        
        return $this->reportRepository->create(
            domainId: $domainId,
            reportDate: $metadata['report_date'],
            reportPeriodStart: new DateTime($metadata['report_period']['start']),
            reportPeriodEnd: new DateTime($metadata['report_period']['end']),
            generatedAt: new DateTime($metadata['generated_at']),
            totalProcessingTime: $metadata['total_processing_time'] ?? 0,
            dataVersion: $metadata['data_version'],
            rawData: $reportData,
            status: $status
        );
    }
}
