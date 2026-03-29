@extends('layouts.app')
@section('title','Edit Laporan POB')
@section('content')
<div class="d-flex">
@include('partials.sidebar')
<main style="flex:1;padding:22px;background:#f0f4f8;">

    <div class="mb-3">
        <a href="{{ route('pob-entries.index') }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Laporan
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:14px;max-width:560px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-0">Edit Laporan POB</h5>
            <p class="text-muted small mb-4">
                {{ $pobEntry->company->name }} — {{ $pobEntry->date->format('d M Y') }}
            </p>

            @if($errors->any())
            <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="border-radius:8px;font-size:.85rem;">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            <form method="POST" action="{{ route('pob-entries.update', $pobEntry) }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tanggal</label>
                    <input type="date" name="date" class="form-control"
                        value="{{ old('date', $pobEntry->date->format('Y-m-d')) }}"
                        max="{{ now()->toDateString() }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Total POB <span class="text-danger">*</span></label>
                        <input type="number" name="total_pob" class="form-control"
                            value="{{ old('total_pob', $pobEntry->total_pob) }}" min="0" required id="inp_pob">
                        <div class="form-text">Karyawan onsite</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Total Manpower <span class="text-danger">*</span></label>
                        <input type="number" name="total_manpower" class="form-control"
                            value="{{ old('total_manpower', $pobEntry->total_manpower) }}" min="0" required id="inp_mp">
                        <div class="form-text">Total tenaga kerja</div>
                    </div>
                </div>

                {{-- Preview rasio --}}
                <div id="ratioBox" class="mb-3 px-3 py-2 rounded" style="background:#eff6ff;font-size:.82rem;">
                    Rasio POB/MP: <strong id="ratioVal">–</strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Dilaporkan Oleh <span class="text-danger">*</span></label>
                    <input type="text" name="informed_by" class="form-control"
                        value="{{ old('informed_by', $pobEntry->informed_by) }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Kontak WhatsApp</label>
                    <input type="text" name="contact_wa" class="form-control"
                        value="{{ old('contact_wa', $pobEntry->contact_wa) }}" placeholder="08xxxxxxxxxx">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold px-4" style="border-radius:8px;">
                        <i class="bi bi-check-circle me-1"></i>Simpan Perubahan
                    </button>
                    <a href="{{ route('pob-entries.index') }}" class="btn btn-outline-secondary px-4" style="border-radius:8px;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
<script>
function updateRatio(){
    const p=parseInt(document.getElementById('inp_pob').value)||0;
    const m=parseInt(document.getElementById('inp_mp').value)||0;
    document.getElementById('ratioVal').textContent = m>0?((p/m)*100).toFixed(1)+'%':'–';
}
document.getElementById('inp_pob').addEventListener('input',updateRatio);
document.getElementById('inp_mp').addEventListener('input',updateRatio);
updateRatio();
</script>
@endsection
