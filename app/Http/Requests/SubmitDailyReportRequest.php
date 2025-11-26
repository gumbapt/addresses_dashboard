<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDailyReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Log ANTES da validação para ver o que está chegando
        \Log::debug('📋 SubmitDailyReportRequest - prepareForValidation', [
            'has_geographic' => $this->has('geographic'),
            'has_geographic_states' => $this->has('geographic.states'),
            'first_state_has_providers' => $this->has('geographic.states.0.providers'),
            'all_keys' => array_keys($this->all()),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Metadata - suporta tanto api_version quanto metadata.data_version
            'api_version' => 'required_without:metadata.data_version|string',
            'metadata.data_version' => 'required_without:api_version|string',
            'report_type' => 'required|string|in:daily',
            'timestamp' => 'required_without:metadata.generated_at|date',
            'metadata.generated_at' => 'required_without:timestamp|date',
            
            // Source information
            'source.site_id' => 'required|string',
            'source.site_name' => 'required|string',
            'source.site_url' => 'required|url',
            'source.wordpress_version' => 'required|string',
            'source.plugin_version' => 'required|string',
            
            // Daily data - suporta tanto data.date quanto metadata.report_date
            'data.date' => 'required_without:metadata.report_date|date|date_format:Y-m-d',
            'metadata.report_date' => 'required_without:data.date|date|date_format:Y-m-d',
            
            // Summary - suporta tanto data.summary quanto summary no nível raiz
            'data.summary.total_requests' => 'required_without:summary.total_requests|integer|min:0',
            'data.summary.successful_requests' => 'required_without:summary.successful_requests|integer|min:0',
            'data.summary.failed_requests' => 'required_without:summary.failed_requests|integer|min:0',
            'data.summary.success_rate' => 'required_without:summary.success_rate|numeric|min:0|max:100',
            'data.summary.unique_providers' => 'required_without:summary.unique_providers|integer|min:0',
            'data.summary.unique_states' => 'required_without:summary.unique_states|integer|min:0',
            'data.summary.unique_cities' => 'required_without:summary.unique_cities|integer|min:0',
            'data.summary.unique_zipcodes' => 'required_without:summary.unique_zip_codes|integer|min:0',
            'data.summary.avg_speed_mbps' => 'required_without:summary.avg_speed_mbps|numeric|min:0',
            'data.summary.max_speed_mbps' => 'required_without:summary.max_speed_mbps|numeric|min:0',
            'data.summary.min_speed_mbps' => 'required_without:summary.min_speed_mbps|numeric|min:0',
            
            // Formato novo: summary no nível raiz
            'summary.total_requests' => 'required_without:data.summary.total_requests|integer|min:0',
            'summary.successful_requests' => 'required_without:data.summary.successful_requests|integer|min:0',
            'summary.failed_requests' => 'required_without:data.summary.failed_requests|integer|min:0',
            'summary.success_rate' => 'required_without:data.summary.success_rate|numeric|min:0|max:100',
            'summary.unique_providers' => 'required_without:data.summary.unique_providers|integer|min:0',
            'summary.unique_states' => 'required_without:data.summary.unique_states|integer|min:0',
            'summary.unique_cities' => 'required_without:data.summary.unique_cities|integer|min:0',
            'summary.unique_zip_codes' => 'required_without:data.summary.unique_zipcodes|integer|min:0',
            'summary.avg_speed_mbps' => 'required_without:data.summary.avg_speed_mbps|numeric|min:0',
            'summary.max_speed_mbps' => 'required_without:data.summary.max_speed_mbps|numeric|min:0',
            'summary.min_speed_mbps' => 'required_without:data.summary.min_speed_mbps|numeric|min:0',
            
            // Geographic data - suporta tanto data.geographic quanto geographic no nível raiz
            'data.geographic.states' => 'required_without:geographic.states|array',
            // Suporta tanto objeto chave-valor quanto array de objetos
            'data.geographic.states.*' => 'nullable',
            // Se for array de objetos (novo formato)
            'data.geographic.states.*.code' => 'required_with:data.geographic.states.*.name|string|size:2',
            'data.geographic.states.*.name' => 'nullable|string|max:100',
            'data.geographic.states.*.request_count' => 'required_with:data.geographic.states.*.code|integer|min:0',
            'data.geographic.states.*.success_rate' => 'nullable|numeric|min:0|max:100',
            'data.geographic.states.*.avg_speed' => 'nullable|numeric|min:0',
            // Campo opcional providers dentro de cada estado
            'data.geographic.states.*.providers' => 'nullable|array',
            'data.geographic.states.*.providers.*.name' => 'required_with:data.geographic.states.*.providers|string|max:255',
            'data.geographic.states.*.providers.*.count' => 'required_with:data.geographic.states.*.providers|integer|min:0',
            'data.geographic.states.*.providers.*.success_rate' => 'nullable|numeric|min:0|max:100',
            'data.geographic.states.*.providers.*.avg_speed' => 'nullable|numeric|min:0',
            'data.geographic.cities' => 'required_without:geographic.top_cities|array',
            'data.geographic.cities.*' => 'integer|min:0',
            'data.geographic.zipcodes' => 'required_without:geographic.top_zip_codes|array',
            'data.geographic.zipcodes.*' => 'integer|min:0',
            'data.geographic.coordinates' => 'sometimes|array',
            'data.geographic.coordinates.*.lat' => 'required_with:data.geographic.coordinates|numeric',
            'data.geographic.coordinates.*.lon' => 'required_with:data.geographic.coordinates|numeric',
            
            // Formato novo: geographic no nível raiz (sem data.)
            'geographic.states' => 'required_without:data.geographic.states|array',
            'geographic.states.*' => 'nullable',
            'geographic.states.*.code' => 'required_with:geographic.states.*.name|string|size:2',
            'geographic.states.*.name' => 'nullable|string|max:100',
            'geographic.states.*.request_count' => 'required_with:geographic.states.*.code|integer|min:0',
            'geographic.states.*.success_rate' => 'nullable|numeric|min:0|max:100',
            'geographic.states.*.avg_speed' => 'nullable|numeric|min:0',
            // Campo opcional providers dentro de cada estado (formato novo)
            'geographic.states.*.providers' => 'nullable|array',
            'geographic.states.*.providers.*.name' => 'required_with:geographic.states.*.providers|string|max:255',
            'geographic.states.*.providers.*.count' => 'required_with:geographic.states.*.providers|integer|min:0',
            'geographic.states.*.providers.*.success_rate' => 'nullable|numeric|min:0|max:100',
            'geographic.states.*.providers.*.avg_speed' => 'nullable|numeric|min:0',
            'geographic.top_cities' => 'required_without:data.geographic.cities|array',
            'geographic.top_cities.*.name' => 'nullable|string|max:255',
            'geographic.top_cities.*.request_count' => 'nullable|integer|min:0',
            'geographic.top_zip_codes' => 'required_without:data.geographic.zipcodes|array',
            'geographic.top_zip_codes.*.zip_code' => 'nullable|string|max:10',
            'geographic.top_zip_codes.*.request_count' => 'nullable|integer|min:0',
            
            // Providers data - suporta tanto data.providers quanto providers no nível raiz
            'data.providers.available' => 'required_without:providers.top_providers|array',
            'data.providers.available.*' => 'integer|min:0',
            'data.providers.excluded' => 'nullable|array',
            'data.providers.excluded.*' => 'integer|min:0',
            
            // Formato novo: providers no nível raiz
            'providers.top_providers' => 'required_without:data.providers.available|array',
            'providers.top_providers.*.name' => 'required_with:providers.top_providers|string|max:255',
            'providers.top_providers.*.total_count' => 'required_with:providers.top_providers|integer|min:0',
            'providers.top_providers.*.technology' => 'nullable|string|max:50',
            'providers.excluded' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'api_version.required' => 'API version is required',
            'report_type.required' => 'Report type is required',
            'report_type.in' => 'Report type must be "daily"',
            'timestamp.required' => 'Timestamp is required',
            'timestamp.date' => 'Timestamp must be a valid date',
            
            'source.site_id.required' => 'Site ID is required',
            'source.site_name.required' => 'Site name is required',
            'source.site_url.required' => 'Site URL is required',
            'source.site_url.url' => 'Site URL must be a valid URL',
            'source.wordpress_version.required' => 'WordPress version is required',
            'source.plugin_version.required' => 'Plugin version is required',
            
            'data.date.required' => 'Report date is required',
            'data.date.date' => 'Report date must be a valid date',
            'data.date.date_format' => 'Report date must be in Y-m-d format',
            
            'data.summary.total_requests.required' => 'Total requests is required',
            'data.summary.total_requests.integer' => 'Total requests must be an integer',
            'data.summary.total_requests.min' => 'Total requests must be 0 or greater',
            
            'data.summary.successful_requests.required' => 'Successful requests is required',
            'data.summary.successful_requests.integer' => 'Successful requests must be an integer',
            'data.summary.successful_requests.min' => 'Successful requests must be 0 or greater',
            
            'data.summary.failed_requests.required' => 'Failed requests is required',
            'data.summary.failed_requests.integer' => 'Failed requests must be an integer',
            'data.summary.failed_requests.min' => 'Failed requests must be 0 or greater',
            
            'data.summary.success_rate.required' => 'Success rate is required',
            'data.summary.success_rate.numeric' => 'Success rate must be a number',
            'data.summary.success_rate.min' => 'Success rate must be 0 or greater',
            'data.summary.success_rate.max' => 'Success rate must be 100 or less',
            
            'data.geographic.states.required' => 'States data is required',
            'data.geographic.states.array' => 'States data must be an array',
            'data.geographic.cities.required' => 'Cities data is required',
            'data.geographic.cities.array' => 'Cities data must be an array',
            'data.geographic.zipcodes.required' => 'Zip codes data is required',
            'data.geographic.zipcodes.array' => 'Zip codes data must be an array',
            
            'data.providers.available.required' => 'Available providers data is required',
            'data.providers.available.array' => 'Available providers data must be an array',
            'data.providers.excluded.array' => 'Excluded providers data must be an array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'api_version' => 'API version',
            'report_type' => 'report type',
            'timestamp' => 'timestamp',
            'source.site_id' => 'site ID',
            'source.site_name' => 'site name',
            'source.site_url' => 'site URL',
            'source.wordpress_version' => 'WordPress version',
            'source.plugin_version' => 'plugin version',
            'data.date' => 'report date',
            'data.summary.total_requests' => 'total requests',
            'data.summary.successful_requests' => 'successful requests',
            'data.summary.failed_requests' => 'failed requests',
            'data.summary.success_rate' => 'success rate',
            'data.summary.unique_providers' => 'unique providers',
            'data.summary.unique_states' => 'unique states',
            'data.summary.unique_cities' => 'unique cities',
            'data.summary.unique_zipcodes' => 'unique zip codes',
            'data.summary.avg_speed_mbps' => 'average speed',
            'data.summary.max_speed_mbps' => 'maximum speed',
            'data.summary.min_speed_mbps' => 'minimum speed',
            'data.geographic.states' => 'states data',
            'data.geographic.cities' => 'cities data',
            'data.geographic.zipcodes' => 'zip codes data',
            'data.providers.available' => 'available providers',
            'data.providers.excluded' => 'excluded providers',
        ];
    }
}
