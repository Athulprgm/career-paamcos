<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    /**
     * Redirect agent target tracking to consolidated Career & Promotion Performance.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('agent.performance');
    }
}
