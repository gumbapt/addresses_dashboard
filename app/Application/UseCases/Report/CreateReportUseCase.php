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
            // Comparar conteúdo para verificar se houve mudança
            $existingRawData = $existingReport->raw_data ?? [];
            $hasChanged = $this->hasReportDataChanged($existingRawData, $reportData);
            
            if (!$hasChanged) {
                // Não houve mudança, retornar report existente sem reprocessar
                return $existingReport->toEntity();
            }
            
            // Normalizar technology_metrics se necessário
            $normalizedData = $this->normalizeTechnologyMetrics($reportData);
            
            // Houve mudança, atualizar o relatório existente
            $existingReport->update([
                'report_period_start' => new DateTime($metadata['report_period']['start']),
                'report_period_end' => new DateTime($metadata['report_period']['end']),
                'generated_at' => new DateTime($metadata['generated_at']),
                'total_processing_time' => $metadata['total_processing_time'] ?? 0,
                'data_version' => $metadata['data_version'],
                'raw_data' => $normalizedData,
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
        
        // Normalizar technology_metrics se necessário
        $normalizedData = $this->normalizeTechnologyMetrics($reportData);
        
        return $this->reportRepository->create(
            domainId: $domainId,
            reportDate: $reportDate,
            reportPeriodStart: new DateTime($metadata['report_period']['start']),
            reportPeriodEnd: new DateTime($metadata['report_period']['end']),
            generatedAt: new DateTime($metadata['generated_at']),
            totalProcessingTime: $metadata['total_processing_time'] ?? 0,
            dataVersion: $metadata['data_version'],
            rawData: $normalizedData,
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

    /**
     * Compara dois reports para verificar se houve mudança no conteúdo
     * Ignora campos de timestamp e metadados que podem variar
     */
    private function hasReportDataChanged(array $existingData, array $newData): bool
    {
        // Normalizar dados removendo campos que podem variar sem mudança real
        $normalizeData = function(array $data) {
            $normalized = [];
            
            // Incluir apenas campos relevantes para comparação
            if (isset($data['summary'])) {
                $normalized['summary'] = $data['summary'];
            }
            if (isset($data['providers'])) {
                $normalized['providers'] = $data['providers'];
            }
            if (isset($data['geographic'])) {
                $normalized['geographic'] = $data['geographic'];
            }
            if (isset($data['performance'])) {
                $normalized['performance'] = $data['performance'];
            }
            if (isset($data['speed_metrics'])) {
                $normalized['speed_metrics'] = $data['speed_metrics'];
            }
            if (isset($data['exclusion_metrics'])) {
                $normalized['exclusion_metrics'] = $data['exclusion_metrics'];
            }
            if (isset($data['technology_metrics'])) {
                $normalized['technology_metrics'] = $data['technology_metrics'];
            }
            if (isset($data['health'])) {
                $normalized['health'] = $data['health'];
            }
            
            // Para daily reports, comparar data.summary e data.providers
            if (isset($data['data']['summary'])) {
                $normalized['data']['summary'] = $data['data']['summary'];
            }
            if (isset($data['data']['providers'])) {
                $normalized['data']['providers'] = $data['data']['providers'];
            }
            if (isset($data['data']['geographic'])) {
                $normalized['data']['geographic'] = $data['data']['geographic'];
            }
            
            return $normalized;
        };
        
        $existingNormalized = $normalizeData($existingData);
        $newNormalized = $normalizeData($newData);
        
        // Ordenar arrays recursivamente para comparação consistente
        $this->ksort_recursive($existingNormalized);
        $this->ksort_recursive($newNormalized);
        
        // Comparar usando hash MD5 para eficiência
        $existingHash = md5(json_encode($existingNormalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $newHash = md5(json_encode($newNormalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        
        return $existingHash !== $newHash;
    }

    /**
     * Ordena arrays recursivamente por chave
     */
    private function ksort_recursive(array &$array): void
    {
        ksort($array);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->ksort_recursive($array[$key]);
            }
        }
    }

    /**
     * Normaliza technology_metrics de diferentes formatos para o formato padrão
     */
    private function normalizeTechnologyMetrics(array $reportData): array
    {
        // Se já existe technology_metrics no formato novo, não precisa converter
        if (isset($reportData['technology_metrics'])) {
            return $reportData;
        }
        
        // Converter formato antigo: data.technologies -> technology_metrics.distribution
        if (isset($reportData['data']['technologies'])) {
            $reportData['technology_metrics'] = [
                'distribution' => $reportData['data']['technologies'],
                'by_state' => [],
                'by_provider' => [],
            ];
            return $reportData;
        }
        
        // Converter formato antigo: technologies (top-level) -> technology_metrics.distribution
        if (isset($reportData['technologies'])) {
            $reportData['technology_metrics'] = [
                'distribution' => $reportData['technologies'],
                'by_state' => [],
                'by_provider' => [],
            ];
            return $reportData;
        }
        
        // CALCULAR a partir de providers.top_providers[].technology (último recurso)
        if (isset($reportData['providers']['top_providers']) && is_array($reportData['providers']['top_providers'])) {
            $technologyDistribution = [];
            
            foreach ($reportData['providers']['top_providers'] as $provider) {
                $technology = $provider['technology'] ?? 'Unknown';
                $count = $provider['total_count'] ?? 0;
                
                if (!isset($technologyDistribution[$technology])) {
                    $technologyDistribution[$technology] = 0;
                }
                $technologyDistribution[$technology] += $count;
            }
            
            if (!empty($technologyDistribution)) {
                $reportData['technology_metrics'] = [
                    'distribution' => $technologyDistribution,
                    'by_state' => [],
                    'by_provider' => [],
                ];
                return $reportData;
            }
        }
        
        // Se não encontrou, retornar sem modificar
        return $reportData;
    }
}
