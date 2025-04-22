<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\info;

class HubSpotController extends Controller
{
    public function index(){
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
                ]
            ];

            // Send batch if we reach 100
            if (count($contacts) === 100) {
                $this->sendBatchToHubspot($contacts);
                $contacts = []; // clear the array
            }
        }

        fclose($file);

        if (!empty($contacts)) {
            $this->sendBatchToHubspot($contacts);
        }

        return back()->with('success', 'CSV uploaded and contacts added to HubSpot.');
    }

    private function sendBatchToHubspot(array $contacts)
    {
        $hubspotToken = env('HUBSPOT_TOKEN');

        $response = Http::withToken($hubspotToken)->post('https://api.hubapi.com/crm/v3/objects/contacts/batch/create', [
            'inputs' => $contacts
        ]);

        if (!$response->successful()) {
            // You can log or throw an error here if needed
            Log::error('HubSpot batch upload failed', [
                'response' => $response->body(),
            ]);
        }
    }
}
