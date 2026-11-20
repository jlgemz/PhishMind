<?php

namespace App\Http\Controllers;

use App\Models\LearningRecord;
use Illuminate\Http\Request;

class AdminResultController extends Controller
{
    public function index()
    {
        // Retrieve student results with their user data
        $results = LearningRecord::with('user')->latest()->paginate(20);
        return view('admin.results.index', compact('results'));
    }
}