<?php

namespace App\Application\UseCases\Report;

use App\Models\Report;
use App\Models\ReportSummary;
use App\Models\ReportProvider;
use App\Models\ReportState;
use App\Models\ReportCity;
use App\Models\ReportZipCode;
use Illuminate\Support\Facades\DB;

class GetReportWithStatsUseCase
{
    public function execute(int $reportId): array
    {
        $report = Report::with(['domain'])->findOrFail($reportId);

        // Buscar summary processado
        $summary = ReportSummary::where('report_id', $reportId)->first();

        // Buscar providers processados
        $providers = $this->getProviders($reportId);

        // Buscar geographic data processado
        $states = $this->getStates($reportId);
        $cities = $this->getCities($reportId);
        $zipCodes = $this->getZipCodes($reportId);

        return [
            'id' => $report->id,
            'domain' => [
                'id' => $report->domain_id,
                'name' => $report->domain->name ?? null,
            ],
            'report_date' => $report->report_date->format('Y-m-d'),
            'report_period' => [
                'start' => $report->report_period_start?->format('Y-m-d H:i:s'),
                'end' => $report->report_period_end?->format('Y-m-d H:i:s'),
            ],
            'generated_at' => $report->generated_at?->format('Y-m-d H:i:s'),
            'data_version' => $report->data_version,
            'status' => $report->status,
            'summary' => $summary ? [
                'total_requests' => $summary->total_requests,
                'failed_requests' => $summary->failed_requests,
                'success_rate' => round($summary->success_rate, 2),
                'avg_requests_per_hour' => round($summary->avg_requests_per_hour, 2),
                'unique_providers' => $summary->unique_providers ?? 0,
                'unique_states' => $summary->unique_states ?? 0,
                'unique_zip_codes' => $summary->unique_zip_codes ?? 0,
            ] : null,
            'providers' => $providers,
            'geographic' => [
                'states' => $states,
                'cities' => $cities,
                'zip_codes' => $zipCodes,
            ],
            'raw_data' => $report->raw_data, // Ainda disponível se necessário
            'created_at' => $report->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $report->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getProviders(int $reportId): array
    {
        $providers = DB::table('report_providers')
            ->join('providers', 'providers.id', '=', 'report_providers.provider_id')
            ->where('report_providers.report_id', $reportId)
            ->select(
                'providers.id',
                'providers.name',
                'providers.slug',
                'report_providers.original_name',
                'report_providers.technology',
                'report_providers.total_count',
                'report_providers.success_rate',
                'report_providers.avg_speed',
                'report_providers.rank_position'
            )
            ->orderBy('report_providers.rank_position')
            ->get();

        return $providers->map(fn($p) => [
            'provider_id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'original_name' => $p->original_name,
            'technology' => $p->technology,
            'total_count' => (int) $p->total_count,
            'success_rate' => round($p->success_rate, 2),
            'avg_speed' => round($p->avg_speed, 2),
            'rank' => $p->rank_position,
        ])->toArray();
    }

    private function getStates(int $reportId): array
    {
        $states = DB::table('report_states')
            ->join('states', 'states.id', '=', 'report_states.state_id')
            ->where('report_states.report_id', $reportId)
            ->select(
                'states.id',
                'states.code',
                'states.name',
                'report_states.request_count',
                'report_states.success_rate',
                'report_states.avg_speed'
            )
            ->orderByDesc('report_states.request_count')
            ->get();

        return $states->map(fn($s) => [
            'state_id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'request_count' => (int) $s->request_count,
            'success_rate' => round($s->success_rate, 2),
            'avg_speed' => round($s->avg_speed, 2),
        ])->toArray();
    }

    private function getCities(int $reportId): array
    {
        $cities = DB::table('report_cities')
            ->join('cities', 'cities.id', '=', 'report_cities.city_id')
            ->where('report_cities.report_id', $reportId)
            ->select(
                'cities.id',
                'cities.name',
                'report_cities.request_count',
                'report_cities.zip_codes'
            )
            ->orderByDesc('report_cities.request_count')
            ->get();

        return $cities->map(fn($c) => [
            'city_id' => $c->id,
            'name' => $c->name,
            'request_count' => (int) $c->request_count,
            'zip_codes' => json_decode($c->zip_codes, true) ?? [],
        ])->toArray();
    }

    private function getZipCodes(int $reportId): array
    {
        $zipCodes = DB::table('report_zip_codes')
            ->join('zip_codes', 'zip_codes.id', '=', 'report_zip_codes.zip_code_id')
            ->where('report_zip_codes.report_id', $reportId)
            ->select(
                'zip_codes.id',
                'zip_codes.code',
                'report_zip_codes.request_count',
                'report_zip_codes.percentage'
            )
            ->orderByDesc('report_zip_codes.request_count')
            ->get();

        return $zipCodes->map(fn($z) => [
            'zip_code_id' => $z->id,
            'code' => $z->code,
            'request_count' => (int) $z->request_count,
            'percentage' => round($z->percentage, 2),
        ])->toArray();
    }
}

