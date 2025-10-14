<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by API key middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Source information
            'source' => 'required|array',
            'source.domain' => 'required|string|max:255',
            'source.site_id' => 'required|string|max:255',
            'source.site_name' => 'required|string|max:255',
            
            // Metadata (required)
            'metadata' => 'required|array',
            'metadata.report_date' => 'required|date_format:Y-m-d',
            'metadata.report_period' => 'required|array',
            'metadata.report_period.start' => 'required|date_format:Y-m-d H:i:s',
            'metadata.report_period.end' => 'required|date_format:Y-m-d H:i:s',
            'metadata.generated_at' => 'required|date_format:Y-m-d H:i:s',
            'metadata.total_processing_time' => 'sometimes|integer|min:0',
            'metadata.data_version' => 'required|string|max:20',
            
            // Summary (required)
            'summary' => 'required|array',
            'summary.total_requests' => 'sometimes|integer|min:0',
            'summary.success_rate' => 'sometimes|numeric|min:0|max:100',
            'summary.failed_requests' => 'sometimes|integer|min:0',
            'summary.avg_requests_per_hour' => 'sometimes|numeric|min:0',
            'summary.unique_providers' => 'sometimes|integer|min:0',
            'summary.unique_states' => 'sometimes|integer|min:0',
            'summary.unique_zip_codes' => 'sometimes|integer|min:0',
            
            // Providers (optional)
            'providers' => 'sometimes|array',
            'providers.top_providers' => 'sometimes|array',
            'providers.top_providers.*.name' => 'required_with:providers.top_providers|string|max:255',
            'providers.top_providers.*.total_count' => 'required_with:providers.top_providers|integer|min:0',
            'providers.top_providers.*.technology' => 'sometimes|string|max:50',
            'providers.top_providers.*.success_rate' => 'sometimes|numeric|min:0|max:100',
            'providers.top_providers.*.avg_speed' => 'sometimes|numeric|min:0',
            'providers.by_state' => 'sometimes|array',
            
            // Geographic (optional)
            'geographic' => 'sometimes|array',
            'geographic.states' => 'sometimes|array',
            'geographic.states.*.code' => 'required_with:geographic.states|string|size:2',
            'geographic.states.*.name' => 'required_with:geographic.states|string|max:100',
            'geographic.states.*.request_count' => 'required_with:geographic.states|integer|min:0',
            'geographic.states.*.success_rate' => 'sometimes|numeric|min:0|max:100',
            'geographic.states.*.avg_speed' => 'sometimes|numeric|min:0',
            'geographic.top_cities' => 'sometimes|array',
            'geographic.top_cities.*.name' => 'required_with:geographic.top_cities|string|max:255',
            'geographic.top_cities.*.request_count' => 'required_with:geographic.top_cities|integer|min:0',
            'geographic.top_cities.*.zip_codes' => 'sometimes|array',
            'geographic.top_zip_codes' => 'sometimes|array',
            'geographic.top_zip_codes.*.zip_code' => 'required_with:geographic.top_zip_codes',
            'geographic.top_zip_codes.*.request_count' => 'required_with:geographic.top_zip_codes|integer|min:0',
            'geographic.top_zip_codes.*.percentage' => 'sometimes|numeric|min:0|max:100',
            
            // Performance (optional)
            'performance' => 'sometimes|array',
            'performance.hourly_distribution' => 'sometimes|array',
            'performance.avg_response_time' => 'sometimes|numeric|min:0',
            'performance.min_response_time' => 'sometimes|numeric|min:0',
            'performance.max_response_time' => 'sometimes|numeric|min:0',
            'performance.search_types' => 'sometimes|array',
            
            // Speed Metrics (optional)
            'speed_metrics' => 'sometimes|array',
            'speed_metrics.overall' => 'sometimes|array',
            'speed_metrics.by_state' => 'sometimes|array',
            'speed_metrics.by_provider' => 'sometimes|array',
            
            // Technology Metrics (optional)
            'technology_metrics' => 'sometimes|array',
            'technology_metrics.distribution' => 'sometimes|array',
            'technology_metrics.by_state' => 'sometimes|array',
            'technology_metrics.by_provider' => 'sometimes|array',
            
            // Exclusion Metrics (optional)
            'exclusion_metrics' => 'sometimes|array',
            'exclusion_metrics.total_exclusions' => 'sometimes|integer|min:0',
            'exclusion_metrics.exclusion_rate' => 'sometimes|numeric|min:0',
            'exclusion_metrics.by_state' => 'sometimes|array',
            'exclusion_metrics.by_provider' => 'sometimes|array',
            
            // Health (optional)
            'health' => 'sometimes|array',
            'health.status' => 'sometimes|string|max:20',
            'health.uptime_percentage' => 'sometimes|numeric|min:0|max:100',
            'health.avg_cpu_usage' => 'sometimes|numeric|min:0|max:100',
            'health.avg_memory_usage' => 'sometimes|numeric|min:0',
            'health.disk_usage' => 'sometimes|numeric|min:0',
            'health.last_cron_run' => 'sometimes|date_format:Y-m-d H:i:s',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'source.required' => 'Source information is required',
            'source.domain.required' => 'Source domain is required',
            'source.site_id.required' => 'Source site ID is required',
            'source.site_name.required' => 'Source site name is required',
            
            'metadata.required' => 'Report metadata is required',
            'metadata.report_date.required' => 'Report date is required',
            'metadata.report_date.date_format' => 'Report date must be in Y-m-d format',
            'metadata.report_period.required' => 'Report period is required',
            'metadata.report_period.start.required' => 'Report period start is required',
            'metadata.report_period.end.required' => 'Report period end is required',
            'metadata.generated_at.required' => 'Generated timestamp is required',
            'metadata.data_version.required' => 'Data version is required',
            
            'summary.required' => 'Report summary is required',
            
            'providers.top_providers.*.name.required_with' => 'Provider name is required when top_providers is provided',
            'providers.top_providers.*.total_count.required_with' => 'Provider total_count is required when top_providers is provided',
            
            'geographic.states.*.code.required_with' => 'State code is required when states is provided',
            'geographic.states.*.code.size' => 'State code must be exactly 2 characters',
            'geographic.states.*.name.required_with' => 'State name is required when states is provided',
            'geographic.states.*.request_count.required_with' => 'State request_count is required when states is provided',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'source.domain' => 'source domain',
            'source.site_id' => 'source site ID',
            'source.site_name' => 'source site name',
            'metadata.report_date' => 'report date',
            'metadata.report_period.start' => 'report period start',
            'metadata.report_period.end' => 'report period end',
            'metadata.generated_at' => 'generated timestamp',
            'metadata.data_version' => 'data version',
        ];
    }
}
