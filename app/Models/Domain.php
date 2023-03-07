<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'code', 'name',
    ];

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

    public function hostnames() {
        return $this->hasMany(Hostname::class);
    }
}
