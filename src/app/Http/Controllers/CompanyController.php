<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    public function index()
    {
        return view('company');
    }

    public function create(Request $request)
    {
        $request->validate([
            'company_csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $hubspotToken = env('HUBSPOT_TOKEN');

        $propertiesResponse = Http::withToken($hubspotToken)->get('https://api.hubapi.com/crm/v3/properties/companies');

        if (!$propertiesResponse->successful()) {
            return back()->withErrors('Failed to fetch HubSpot properties.');
        }

        $propertiesData = $propertiesResponse->json()['results'];
        $validProperties = collect($propertiesData)->pluck('name')->toArray();


        $industryOptions = collect($propertiesData)->firstWhere('name', 'industry')['options'] ?? [];

        $validIndustryValues = collect($industryOptions)->pluck('value')->map(fn($v) => strtoupper($v))->toArray();

        $file = fopen($request->file('company_csv_file'), 'r');
        $header = fgetcsv($file);

        $companies = [];

        while ($row = fgetcsv($file)) {
            $data = array_combine($header, $row);
            $properties = [];

            foreach ($data as $key => $value) {

                if (in_array($key, $validProperties) && !empty($value)) {
                    if ($key === 'industry') {
                        $upperIndustry = strtoupper($value);
                        if (in_array($upperIndustry, $validIndustryValues)) {
                            $properties[$key] = $upperIndustry;
                        }
                    } else {
                        $properties[$key] = $value;
                    }
                }
            }

            if (!empty($properties)) {
                $companies[] = ['properties' => $properties];
            }

            if (count($companies) === 100) {
                $this->sendBatchToHubspot($companies);
                $this->saveDataInLocalDB($companies);
                $companies = [];
            }
        }

        fclose($file);

        if (!empty($companies)) {
            $this->sendBatchToHubspot($companies);
            $this->saveDataInLocalDB($companies);

        }

        return back()->with('success', 'CSV uploaded and companies added to HubSpot.');
    }

    private function sendBatchToHubspot(array $companies)
    {
        $hubspotToken = env('HUBSPOT_TOKEN');

        $response = Http::withToken($hubspotToken)->post('https://api.hubapi.com/crm/v3/objects/companies/batch/create', [
            'inputs' => $companies,
        ]);

        if (!$response->successful()) {
            Log::error('HubSpot batch upload failed', [
                'response' => $response->body(),
            ]);
        }
    }

    private function saveDataInLocalDB(array $companies){
        foreach ($companies as $companiestData) {
            $props = $companiestData['properties'];

            Company::create([
                'name' => $props['name'],
                'domain' => $props['domain'],
                'phone' => $props['phone'],
                'industry' => $props['industry'] ?? null
            ]);
        }
    }
}
