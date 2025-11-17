<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generator nomor berurutan: {PREFIX}-{YYYYMM}-{####}
 * Contoh: PO-202510-0001, PO-202510-0002, dst (reset tiap bulan).
 */
class RunningNumber
{
    /**
     * Buat nomor berikutnya.
     *
     * @param  string      $prefix   Mis: 'PO'
     * @param  string      $table    Nama tabel yang menyimpan nomor (mis: 'purchases')
     * @param  string      $column   Nama kolom nomor (mis: 'po_no')
     * @param  \DateTimeInterface|string|null $date  (opsional) acu tanggal untuk YYYYMM
     * @return string
     */
    public static function next(string $prefix, string $table, string $column, $date = null): string
    {
        $ts = $date ? (is_string($date) ? strtotime($date) : $date->getTimestamp()) : time();
        $ym = date('Ym', $ts); // YYYYMM
        $pattern = "{$prefix}-{$ym}-";

        // Ambil nomor terakhir bulan ini (urut lexicographic aman karena zero-pad 4 digit)
        $last = DB::table($table)
            ->where($column, 'like', $pattern.'%')
            ->orderByDesc($column)
            ->value($column);

        $seq = 1;
        if ($last) {
            // format expected: PREFIX-YYYYMM-#### -> ambil 4 digit terakhir
            $parts = explode('-', $last);
            $lastSeq = (int) (end($parts) ?: 0);
            $seq = $lastSeq + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $ym, $seq);
    }
}
