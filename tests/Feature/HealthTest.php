<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_versioned_health_endpoint_returns_a_public_envelope(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson([
            'data' => ['status' => 'ok', 'service' => 'portfolio-api', 'version' => 'v1'],
        ]);
    }

    public function test_unknown_api_routes_return_json(): void
    {
        $this->get('/api/v1/missing')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_cors_allows_the_configured_frontend_only(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:3000'])->getJson('/api/v1/health')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        // A single allowed origin is returned as a fixed value. The browser
        // rejects it when it does not match the requesting origin.
        $response = $this->withHeaders(['Origin' => 'https://untrusted.example'])->getJson('/api/v1/health');
        $this->assertNotContains($response->headers->get('Access-Control-Allow-Origin'), ['*', 'https://untrusted.example']);
    }
}
