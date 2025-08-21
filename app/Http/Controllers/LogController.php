<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\User;


class LogController extends Controller
{

    public function index()
    {
        $logs = Log::all();
        return view('logs.index', compact('logs'));
    }
}
