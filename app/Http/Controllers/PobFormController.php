<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEntry;
use Illuminate\Http\Request;

class PobFormController extends Controller
{
    public function index()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('form.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id'     => 'required|exists:companies,id',
            'date'           => 'required|date|before_or_equal:today',
            'total_pob'      => 'required|integer|min:0',
            'total_manpower' => 'required|integer|min:0|gte:total_pob',
            'informed_by'    => 'required|string|max:100',
            'contact_wa'     => 'required|string|max:30',
            'submitted_by'   => 'nullable|string|max:100',
            'submitted_email'=> 'nullable|email|max:100',
        ], [
            'total_manpower.gte' => 'Total Manpower harus lebih besar atau sama dengan Total POB.',
        ]);

        PobEntry::updateOrCreate(
            ['company_id' => $validated['company_id'], 'date' => $validated['date']],
            $validated
        );

        return redirect()->route('form.index')
            ->with('success', 'Data POB berhasil disimpan untuk tanggal ' . $validated['date'] . '.');
    }
}