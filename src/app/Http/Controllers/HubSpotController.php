<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubSpotController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = fopen($request->file('csv_file'), 'r');
        $header = fgetcsv($file);

        $contacts = [];

        while ($row = fgetcsv($file)) {
            $data = array_combine($header, $row);

            $contacts[] = [
                'properties' => [
                    'firstname' => $data['firstname'] ?? '',
                    'lastname' => $data['lastname'] ?? '',
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'company' => $data['company'] ?? '',
                ],
            ];

            if (count($contacts) === 100) {
                $results = $this->sendBatchToHubspot($contacts);
                $this->saveBatchToLocalDB($contacts, $results);
                $contacts = [];
            }
        }

        fclose($file);

        if (!empty($contacts)) {
            $results = $this->sendBatchToHubspot($contacts);
            $this->saveBatchToLocalDB($contacts, $results);
        }

        return back()->with('success', 'CSV uploaded and contacts added to HubSpot.');
    }

    private function sendBatchToHubspot(array $contacts)
    {
        $hubspotToken = env('HUBSPOT_TOKEN');

        $response = Http::withToken($hubspotToken)->post('https://api.hubapi.com/crm/v3/objects/contacts/batch/create', [
            'inputs' => $contacts,
        ]);

        if (!$response->successful()) {
            Log::error('HubSpot batch upload failed', [
                'response' => $response->body(),
            ]);
            return [];
        }

        return $response->json()['results'] ?? [];
    }

    private function saveBatchToLocalDB(array $contacts, array $hubspotResults)
    {
        foreach ($contacts as $contactData) {
            $props = $contactData['properties'];
            $email = $props['email'];

            $hubspotContact = collect($hubspotResults)->first(function ($result) use ($email) {
                return strtolower($result['properties']['email']) === strtolower($email);
            });

            $hubspotId = $hubspotContact['id'] ?? null;

            Contact::create([
                'firstname' => $props['firstname'],
                'lastname' => $props['lastname'],
                'email' => $props['email'],
                'phone' => $props['phone'],
                'company' => $props['company'],
                'hubspot_id' => $hubspotId,
            ]);
        }
    }
}
