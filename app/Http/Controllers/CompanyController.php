<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PobEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');

        $query = Company::withCount(['pobEntries as total_reports'])
            ->withCount(['employees as total_employees'])
            ->withCount(['contacts as total_contacts']);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status === 'active')   $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        $companies = $query->orderBy('name')->paginate(20);

        return view('dashboard.companies.index', compact('companies', 'search', 'status'));
    }

    public function create()
    {
        return view('dashboard.companies.form', ['company' => null]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:200|unique:companies,name',
            'type' => 'required|string|max:50',
        ], [
            'name.unique' => 'Nama perusahaan sudah terdaftar.',
        ]);

        Company::create([
            'name'      => $v['name'],
            'slug'      => Str::slug($v['name']),
            'type'      => $v['type'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('companies.index')
            ->with('success', "Perusahaan \"{$v['name']}\" berhasil ditambahkan.");
    }

    public function edit(Company $company)
    {
        return view('dashboard.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $v = $request->validate([
            'name' => "required|string|max:200|unique:companies,name,{$company->id}",
            'type' => 'required|string|max:50',
        ], [
            'name.unique' => 'Nama perusahaan sudah digunakan perusahaan lain.',
        ]);

        $company->update([
            'name'      => $v['name'],
            'slug'      => Str::slug($v['name']),
            'type'      => $v['type'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('companies.index')
            ->with('success', "Data \"{$v['name']}\" berhasil diperbarui.");
    }

    public function destroy(Company $company)
    {
        $reports = PobEntry::where('company_id', $company->id)->count();
        if ($reports > 0) {
            return back()->with('error', "Tidak dapat menghapus \"{$company->name}\" karena memiliki {$reports} laporan POB.");
        }

        $name = $company->name;
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', "Perusahaan \"{$name}\" berhasil dihapus.");
    }

    public function toggle(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);
        $status = $company->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "\"{$company->name}\" berhasil {$status}.");
    }
}
