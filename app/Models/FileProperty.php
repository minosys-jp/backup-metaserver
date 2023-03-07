<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'root_dir_id', 'name', 'description', 'renamed_file_id',
    ];

    public function root_dir() {
        return $this->belongsTo(RootDir::class);
    }

    public function file_logs() {
        return $this->hasMany(FileLog::class);
    }
}
