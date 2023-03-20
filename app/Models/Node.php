<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    const public FLG_FILE = 0;
    const public FLG_DIR = 1;

    protected $fillable = [
        'root_dir_id', 'parent_id', 'flg_dir', 'name', 'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function root_dir() {
        return $this->belongsTo(RootDir::class);
    }

    public function node_log() {
        return $this->hasMany(NodeLog::class);
    }
}
