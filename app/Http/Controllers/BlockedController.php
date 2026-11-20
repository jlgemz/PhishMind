<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\URLScan;

class BlockedController extends Controller
{
    public function index(Request $request)
    {
        $url = $request->query('url');
        $scanId = $request->query('scan_id');
        $scan = null;
        
        if ($scanId) {
            $scan = URLScan::find($scanId);
        } elseif ($url) {
            $scan = URLScan::where('url', urldecode($url))->first();
        }
        
        $reason = 'This URL was blocked for your safety.';
        if ($scan) {
            $reason = 'This URL was detected as a phishing site with ' . 
                      round($scan->confidence_score) . '% confidence.';
        }
        
        return view('phishguard.blocked', [
            'url' => $url,
            'scan' => $scan,
            'reason' => $reason,
            'risk_indicators' => $scan ? $scan->scan_results : []
        ]);
    }

    public function proceed(Request $request)
    {
        $url = $request->input('url');
        
        if ($url) {
            // Add to allowlist temporarily
            session()->put('allowed_urls', array_merge(
                session()->get('allowed_urls', []),
                [urldecode($url)]
            ));
            
            return redirect()->away(urldecode($url));
        }
        
        return redirect()->route('home');
    }
}
