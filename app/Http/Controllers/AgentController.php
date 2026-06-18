<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    /**
     * Display the Agent panel dashboard.
     */
    public function index()
    {
        return view('agent', [
            'user' => Auth::user()
        ]);
    }
}
