<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FineController extends Controller
{
    public function index(): View
    {
        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value') ?? '0.50';

        return view('fines.index', compact('rate'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::table('settings')->where('key', 'fine_per_day')->update([
            'value' => sprintf('%.2F', $validated['rate']),
            'updated_at' => now(),
        ]);

        return redirect()->route('fines.index')
            ->with('success', 'Soda likme mainīta uz ' . number_format((float) $validated['rate'], 2) . ' EUR/dienā!');
    }
}
