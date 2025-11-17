<?php

namespace App\Http\Controllers;

use App\Models\GolonganObat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GolonganObatController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));

        $golongan = GolonganObat::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('golongan.index', compact('golongan', 'q'));
    }

    public function create()
    {
        // Supaya form bisa pakai $golonganObat->... tanpa undefined
        $golonganObat = new GolonganObat([
            'is_active' => true,
        ]);

        return view('golongan.create', compact('golonganObat'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required','string','max:50', Rule::unique('drug_groups','code')],
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        GolonganObat::create($data);

        return redirect()
            ->route('golongan-obat.index')
            ->with('ok', 'Golongan obat berhasil ditambahkan.');
    }

    public function edit(GolonganObat $golonganObat)
    {
        return view('golongan.edit', compact('golonganObat'));
    }

    public function update(Request $request, GolonganObat $golonganObat)
    {
        $data = $request->validate([
            'code'        => ['required','string','max:50', Rule::unique('drug_groups','code')->ignore($golonganObat->id)],
            'name'        => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $golonganObat->update($data);

        return redirect()
            ->route('golongan-obat.index')
            ->with('ok', 'Golongan obat berhasil diubah.');
    }

    public function destroy(GolonganObat $golonganObat)
    {
        $golonganObat->delete();

        return back()->with('ok', 'Golongan obat dihapus.');
    }
}
