<?php

namespace App\Http\Controllers;

use App\Models\LokasiObat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LokasiObatController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $rows = LokasiObat::query()
            ->when($q, function ($w) use ($q) {
                $w->where(function ($x) use ($q) {
                    $x->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
                });
            })
            // Urutkan yang ada sort_order dulu, lalu berdasarkan nilainya, lalu nama
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('lokasi-obat.index', compact('rows', 'q'));
    }

    public function create()
    {
        $lokasiObat = new \App\Models\LokasiObat();
        return view('lokasi-obat.create', compact('lokasiObat')); // ← cocok dengan path
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required','string','max:50', Rule::unique('drug_locations','code')],
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'sort_order'  => ['nullable','integer','min:0','max:999999'],
        ]);

        LokasiObat::create($data);

        return redirect()->route('lokasi-obat.index')->with('ok','Lokasi obat berhasil ditambahkan.');
    }

    public function show(LokasiObat $lokasiObat)
    {
        return view('lokasi-obat.show', compact('lokasiObat'));
    }

    public function edit(\App\Models\LokasiObat $lokasiObat) // ← nama variabel harus "lokasiObat"
    {
        return view('lokasi-obat.edit', compact('lokasiObat'));
    }

    public function update(Request $request, LokasiObat $lokasiObat)
    {
        $data = $request->validate([
            'code'        => ['required','string','max:50', Rule::unique('drug_locations','code')->ignore($lokasiObat->id)],
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'sort_order'  => ['nullable','integer','min:0','max:999999'],
        ]);

        $lokasiObat->update($data);

        return redirect()->route('lokasi-obat.index')->with('ok','Lokasi obat berhasil diubah.');
    }

    public function destroy(LokasiObat $lokasiObat)
    {
        $lokasiObat->delete();

        return back()->with('ok','Lokasi obat dihapus.');
    }
}
