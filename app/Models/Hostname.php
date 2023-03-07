<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hostname extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id', 'code', 'name'
    ];

    public function doamin() {
        return $this->belongsTo(Domain::class);
    }

    public function root_dirs() {
        return $this->hasMany(RootDir::class);
    }
}
