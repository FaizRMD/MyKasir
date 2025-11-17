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
            'items.*.qty'              => 'required',
            'items.*.uom'              => 'nullable|string|max:50',
            'items.*.buy_price'        => 'nullable',
            'items.*.disc_percent'     => 'nullable',
            'items.*.disc_amount'      => 'nullable',
            'items.*.batch_no'         => 'nullable|string|max:100',
            'items.*.exp_date'         => 'nullable|date',
        ];
    }

    protected function prepareForValidation(): void
    {
        $norm = function ($v) {
            if ($v === null) return null;
            if (is_numeric($v)) return $v + 0;
            $s = (string)$v;
            if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
                return is_numeric($s) ? $s + 0 : $v;
            }
            if (strpos($s, ',') !== false) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
                return is_numeric($s) ? $s + 0 : $v;
            }
            $s = preg_replace('/[^\d\.]/', '', $s);
            if (substr_count($s, '.') > 1) {
                $digits = str_replace('.', '', $s);
                if (preg_match('/^\d+$/', $digits)) {
                    if (strlen($digits) > 2) {
                        $s = substr($digits, 0, -2) . '.' . substr($digits, -2);
                    } else {
                        $s = $digits;
                    }
                }
            }
            return is_numeric($s) ? $s + 0 : $v;
        };

        $data = $this->all();

        $data['tax_percent'] = $norm($data['tax_percent'] ?? 0);
        $data['extra_cost']  = $norm($data['extra_cost'] ?? 0);

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $i => $row) {
                $data['items'][$i]['qty']          = $norm($row['qty'] ?? 0);
                $data['items'][$i]['buy_price']    = $norm($row['buy_price'] ?? 0);
                $data['items'][$i]['disc_percent'] = $norm($row['disc_percent'] ?? 0);
                $data['items'][$i]['disc_amount']  = $norm($row['disc_amount'] ?? 0);
            }
        }

        $this->replace($data);
    }
}
