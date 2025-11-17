<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Apoteker extends Model
{
    use HasFactory;

    protected $table = 'apotekers';   // sesuai nama tabel di migration

    protected $fillable = [
        'nip','name','sip','sip_valid_until','phone','email','address','is_active'
    ];

    protected $casts = [
        'sip_valid_until'=>'date',
        'is_active'=>'boolean'
    ];
}
