<?php

namespace Vega\FaceLogin\Models;

use Illuminate\Database\Eloquent\Model;

class FaceViolation extends Model
{
    protected $fillable = ['user_id', 'type'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
