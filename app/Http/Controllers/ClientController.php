<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::withCount(['invoices'])
            ->with(['invoices.lineItems', 'workLogs'])
            ->orderBy('name')
            ->get();

        return view('clients.index', compact('clients'));
    }

    public function show(Client $client): View
    {
        $client->load([
            'invoices.lineItems',
            'invoices.client',
            'workLogs',
        ]);

        return view('clients.show', compact('client'));
    }
}
