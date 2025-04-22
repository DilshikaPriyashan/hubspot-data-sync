<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        dump($header);
        $hubspotToken = env('HUBSPOT_TOKEN');

        while ($row = fgetcsv($file)) {
            $data = array_combine($header, $row);
            dd($data);

            $response = Http::withToken($hubspotToken)->post('https://api.hubapi.com/crm/v3/objects/contacts', [
                'properties' => [
                    'firstname' => $data['firstname'] ?? '',
                    'lastname' => $data['lastname'] ?? '',
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'company' => $data['company'] ?? '',
                ],
            ]);
            // dd($response);

            if (!$response->successful()) {
                info($response);
            }
        }

        fclose($file);

        return back()->with('success', 'CSV uploaded and contacts added to HubSpot.');
    }
}
