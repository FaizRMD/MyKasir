<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_no'         => 'nullable|string',
            'invoice_no'    => 'nullable|string|max:100',
            'invoice_date'  => 'required|date',
            'supplier_id'   => 'required|integer|exists:suppliers,id',
            'warehouse_id'  => 'nullable|integer|exists:warehouses,id',
            'payment_type'  => 'required|string',
            'cashbook'      => 'nullable|string',
            'due_date'      => 'nullable|date',
            'tax_percent'   => 'nullable|numeric',
            'extra_cost'    => 'nullable|numeric',
            'notes'         => 'nullable|string',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'       => 'required|integer|exists:products,id',
            'items.*.qty'              => 'required|numeric|min:0.01',
            'items.*.uom'              => 'nullable|string|max:50',
            'items.*.buy_price'        => 'required|numeric|min:0',
            'items.*.disc_percent'     => 'nullable|numeric|min:0|max:100',
            'items.*.disc_nominal'     => 'nullable|numeric|min:0',
            'items.*.batch_no'         => 'nullable|string|max:100',
            'items.*.exp_date'         => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item pembelian',
            'items.*.qty.required' => 'Qty wajib diisi',
            'items.*.qty.min' => 'Qty minimal 0.01',
            'items.*.buy_price.required' => 'Harga beli wajib diisi',
            'items.*.buy_price.min' => 'Harga beli tidak boleh negatif',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Helper function untuk normalisasi angka
        $normalize = function ($value) {
            if ($value === null || $value === '') {
                return 0;
            }

            if (is_numeric($value)) {
                return floatval($value);
            }

            $str = trim(strval($value));

            // Format Indonesia: 1.000.000,50 atau 1.000,50
            if (strpos($str, ',') !== false) {
                // Hapus titik pemisah ribuan
                $str = str_replace('.', '', $str);
                // Ganti koma desimal jadi titik
                $str = str_replace(',', '.', $str);
            }

            // Bersihkan karakter non-numeric kecuali titik desimal
            $str = preg_replace('/[^\d\.]/', '', $str);

            // Handle multiple dots (ambil 2 digit terakhir sebagai desimal)
            if (substr_count($str, '.') > 1) {
                $parts = explode('.', $str);
                $decimals = array_pop($parts);
                $str = implode('', $parts) . '.' . $decimals;
            }

            return is_numeric($str) ? floatval($str) : 0;
        };

        $data = $this->all();

        // Normalisasi header
        $data['tax_percent'] = $normalize($data['tax_percent'] ?? 0);
        $data['extra_cost']  = $normalize($data['extra_cost'] ?? 0);

        // Normalisasi items
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $i => $row) {
                $data['items'][$i]['qty']          = $normalize($row['qty'] ?? 0);
                $data['items'][$i]['buy_price']    = $normalize($row['buy_price'] ?? 0);
                $data['items'][$i]['disc_percent'] = $normalize($row['disc_percent'] ?? 0);
                $data['items'][$i]['disc_nominal'] = $normalize($row['disc_nominal'] ?? 0);

                // Pastikan UOM ada default value
                if (empty($data['items'][$i]['uom'])) {
                    $data['items'][$i]['uom'] = 'PCS';
                }
            }
        }

        $this->replace($data);
    }
}
