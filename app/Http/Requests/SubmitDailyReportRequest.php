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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Metadata
            'api_version' => 'required|string',
            'report_type' => 'required|string|in:daily',
            'timestamp' => 'required|date',
            
            // Source information
            'source.site_id' => 'required|string',
            'source.site_name' => 'required|string',
            'source.site_url' => 'required|url',
            'source.wordpress_version' => 'required|string',
            'source.plugin_version' => 'required|string',
            
            // Daily data
            'data.date' => 'required|date|date_format:Y-m-d',
            
            // Summary
            'data.summary.total_requests' => 'required|integer|min:0',
            'data.summary.successful_requests' => 'required|integer|min:0',
            'data.summary.failed_requests' => 'required|integer|min:0',
            'data.summary.success_rate' => 'required|numeric|min:0|max:100',
            'data.summary.unique_providers' => 'required|integer|min:0',
            'data.summary.unique_states' => 'required|integer|min:0',
            'data.summary.unique_cities' => 'required|integer|min:0',
            'data.summary.unique_zipcodes' => 'required|integer|min:0',
            'data.summary.avg_speed_mbps' => 'required|numeric|min:0',
            'data.summary.max_speed_mbps' => 'required|numeric|min:0',
            'data.summary.min_speed_mbps' => 'required|numeric|min:0',
            
            // Geographic data
            'data.geographic.states' => 'required|array',
            'data.geographic.states.*' => 'integer|min:0',
            'data.geographic.cities' => 'required|array',
            'data.geographic.cities.*' => 'integer|min:0',
            'data.geographic.zipcodes' => 'required|array',
            'data.geographic.zipcodes.*' => 'integer|min:0',
            'data.geographic.coordinates' => 'sometimes|array',
            'data.geographic.coordinates.*.lat' => 'required_with:data.geographic.coordinates|numeric',
            'data.geographic.coordinates.*.lon' => 'required_with:data.geographic.coordinates|numeric',
            
            // Providers data
            'data.providers.available' => 'required|array',
            'data.providers.available.*' => 'integer|min:0',
            'data.providers.excluded' => 'required|array',
            'data.providers.excluded.*' => 'integer|min:0',
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
            'data.providers.excluded.required' => 'Excluded providers data is required',
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
