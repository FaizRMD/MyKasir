<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // halaman laporan hanya untuk user login
    }

    public function rules(): array
    {
        return [
            'supplier_ids'   => ['nullable','array'],
            'supplier_ids.*' => ['integer','exists:suppliers,id'],

            'date_from' => ['nullable','date'],
            'date_to'   => ['nullable','date','after_or_equal:date_from'],

            'min_total' => ['nullable','numeric'],
            'max_total' => ['nullable','numeric','gte:min_total'],

            'export'    => ['nullable','in:csv,pdf'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        // Normalisasi agar konsisten
        $data['supplier_ids'] = $data['supplier_ids'] ?? [];
        $data['date_from']    = $data['date_from']    ?? null;
        $data['date_to']      = $data['date_to']      ?? null;
        $data['min_total']    = $data['min_total']    ?? null;
        $data['max_total']    = $data['max_total']    ?? null;
        $data['export']       = $data['export']       ?? null;
        return $data;
    }
}
