<?php

namespace App\Application\DTOs\Domain;

class DomainDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $domain_url,
        public readonly string $site_id,
        public readonly string $api_key,
        public readonly string $status,
        public readonly string $timezone,
        public readonly string $wordpress_version,
        public readonly string $plugin_version,
        public readonly array $settings,
        public readonly bool $is_active,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain_url' => $this->domain_url,
            'site_id' => $this->site_id,
            'api_key' => $this->api_key,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'wordpress_version' => $this->wordpress_version,
            'plugin_version' => $this->plugin_version,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
        ];
    }
}