<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEmployee;
use App\Models\PobEntry;
use Illuminate\Http\Request;

class PobEntryController extends Controller
{
    public function index(Request $request)
    {
        $from      = $request->get('from', now()->startOfMonth()->toDateString());
        $to        = $request->get('to',   now()->toDateString());
        $companyId = $request->get('company_id');
        $search    = $request->get('search');

        $query = PobEntry::with('company')
            ->whereBetween('date', [$from, $to])
            ->whereRaw("id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))");

        if ($companyId) $query->where('company_id', $companyId);
        if ($search)    $query->whereHas('company', fn($q) => $q->where('name','like',"%{$search}%"));

        $entries   = $query->orderBy('date','desc')->orderBy('company_id')->paginate(30);
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        // Summary
        $total_pob = PobEntry::whereBetween('date',[$from,$to])
            ->whereRaw("id IN (SELECT MAX(id) FROM pob_entries GROUP BY company_id, DATE(date))")
            ->sum('total_pob');

        return view('dashboard.pob_entries.index', compact(
            'entries', 'companies', 'from', 'to', 'companyId', 'search', 'total_pob'
        ));
    }

    public function edit(PobEntry $pobEntry)
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('dashboard.pob_entries.edit', compact('pobEntry', 'companies'));
    }

    public function update(Request $request, PobEntry $pobEntry)
    {
        $v = $request->validate([
            'total_pob'      => 'required|integer|min:0',
            'total_manpower' => 'required|integer|min:0|gte:total_pob',
            'informed_by'    => 'required|string|max:100',
            'contact_wa'     => 'nullable|string|max:30',
            'date'           => 'required|date',
        ], [
            'total_manpower.gte' => 'Manpower tidak boleh kurang dari POB.',
        ]);

        $pobEntry->update($v);

        return redirect()->route('pob-entries.index')
            ->with('success', "Laporan POB berhasil diperbarui.");
    }

    public function destroy(PobEntry $pobEntry)
    {
        // Hapus juga data karyawan terkait (cascade)
        PobEmployee::where('pob_entry_id', $pobEntry->id)->delete();
        $info = $pobEntry->company->name . ' — ' . $pobEntry->date->format('d M Y');
        $pobEntry->delete();

        return redirect()->route('pob-entries.index')
            ->with('success', "Laporan \"{$info}\" berhasil dihapus.");
    }

    public function show(PobEntry $pobEntry)
    {
        $pobEntry->load('company');
        $employees = PobEmployee::where('pob_entry_id', $pobEntry->id)
            ->orderBy('name')->paginate(50);
        return view('dashboard.pob_entries.show', compact('pobEntry', 'employees'));
    }

    // Hapus 1 karyawan dari entry
    public function destroyEmployee(PobEmployee $employee)
    {
        $entryId = $employee->pob_entry_id;
        $name    = $employee->name;
        $employee->delete();

        // Update total_pob
        $count = PobEmployee::where('pob_entry_id', $entryId)->count();
        PobEntry::where('id', $entryId)->update(['total_pob' => $count]);

        return back()->with('success', "\"{$name}\" berhasil dihapus dari daftar POB.");
    }
}
