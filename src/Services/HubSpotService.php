<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HubSpotService
{
    protected $baseUrl = 'https://api.hubapi.com';

    public function getContacts()
    {
        $response = Http::withToken(config('services.hubspot.token'))
            ->get("{$this->baseUrl}/crm/v3/properties/contact/education_level");

        if ($response->successful()) {
            return $response->json();
        }

        return $response->throw();
    }
}
