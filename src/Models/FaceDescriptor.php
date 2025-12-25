<?php

namespace Vega\FaceLogin\Models;

use Illuminate\Database\Eloquent\Model;

class FaceDescriptor extends Model
{
    protected $fillable = ['center_code', 'descriptor'];

    protected $casts = [
        'descriptor' => 'array',
    ];
}
