<?php

namespace App\Http\Controllers;

use App\Models\Apoteker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApotekerController extends Controller
{
    /**
     * Tampilkan daftar apoteker + pencarian
     */
    public function index(Request $request)
    {
        $q = $request->get('q');

        $rows = Apoteker::query()
            ->when($q, fn($w) => $w->where(function($x) use ($q) {
                $x->where('name','like',"%{$q}%")
                  ->orWhere('nip','like',"%{$q}%")
                  ->orWhere('sip','like',"%{$q}%")
                  ->orWhere('phone','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('apoteker.index', compact('rows','q'));
    }

    /**
     * Form tambah apoteker
     */
    public function create()
    {
        return view('apoteker.create');
    }

    /**
     * Simpan apoteker baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nip'            => ['nullable','string','max:32','unique:apotekers,nip'],
            'name'           => ['required','string','max:255'],
            'sip'            => ['nullable','string','max:64'],
            'sip_valid_until'=> ['nullable','date'],
            'phone'          => ['nullable','string','max:64'],
            'email'          => ['nullable','email','max:128'],
            'address'        => ['nullable','string'],
            'is_active'      => ['nullable','boolean'],
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? true);

        Apoteker::create($data);

        return redirect()->route('apoteker.index')->with('ok','Apoteker berhasil ditambahkan');
    }

    /**
     * Detail apoteker
     */
    public function show(Apoteker $apoteker)
    {
        return view('apoteker.show', compact('apoteker'));
    }

    /**
     * Form edit
     */
    public function edit(Apoteker $apoteker)
    {
        return view('apoteker.edit', compact('apoteker'));
    }

    /**
     * Update data apoteker
     */
    public function update(Request $request, Apoteker $apoteker)
    {
        $data = $request->validate([
            'nip'            => ['nullable','string','max:32', Rule::unique('apotekers','nip')->ignore($apoteker->id)],
            'name'           => ['required','string','max:255'],
            'sip'            => ['nullable','string','max:64'],
            'sip_valid_until'=> ['nullable','date'],
            'phone'          => ['nullable','string','max:64'],
            'email'          => ['nullable','email','max:128'],
            'address'        => ['nullable','string'],
            'is_active'      => ['nullable','boolean'],
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? true);

        $apoteker->update($data);

        return redirect()->route('apoteker.index')->with('ok','Apoteker berhasil diperbarui');
    }

    /**
     * Hapus apoteker
     */
    public function destroy(Apoteker $apoteker)
    {
        $apoteker->delete();
        return back()->with('ok','Apoteker berhasil dihapus');
    }
}
