<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request){
        $q = $request->string('q')->toString();
        $customers = Customer::when($q, fn($qq)=>$qq->where('name','like',"%{$q}%"))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('customers.index', compact('customers','q'));
    }

    public function create(){ return view('customers.create'); }

    public function store(Request $request){
        $data = $request->validate([
            'code'=>['nullable','string','max:64','unique:customers,code'],
            'name'=>['required','string','max:255'],
            'phone'=>['nullable','string','max:64'],
            'address'=>['nullable','string','max:255'],
            'notes'=>['nullable','string','max:255'],
        ]);
        Customer::create($data);
        return redirect()->route('customers.index')->with('ok','Pelanggan dibuat.');
    }

    public function show(Customer $customer){ return view('customers.show', compact('customer')); }

    public function edit(Customer $customer){ return view('customers.edit', compact('customer')); }

    public function update(Request $request, Customer $customer){
        $data = $request->validate([
            'code'=>['nullable','string','max:64','unique:customers,code,'.$customer->id],
            'name'=>['required','string','max:255'],
            'phone'=>['nullable','string','max:64'],
            'address'=>['nullable','string','max:255'],
            'notes'=>['nullable','string','max:255'],
        ]);
        $customer->update($data);
        return redirect()->route('customers.index')->with('ok','Pelanggan diubah.');
    }

    public function destroy(Customer $customer){
        $customer->delete();
        return back()->with('ok','Pelanggan dihapus.');
    }
}
