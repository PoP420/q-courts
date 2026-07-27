<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Court;

class CourtController extends Controller
{
    /** GET /api/courts */
    public function index()
    {
        return Court::with('activeSession')->where('is_active', true)->get();
    }
}
