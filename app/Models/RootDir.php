<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RootDir extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostname_id', 'name', 'description',
    ];

    public function hostname() {
        return $this->belongsTo(Hostname::class);
    }

    public function nodes() {
        return $this->hasMany(Node::class);
    }
}
