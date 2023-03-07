<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_slice_id', 'file_property_id', 'start_slice_number', 'last_slice_number', 'max_slice_number', 'slice_number', 'slice_offset', 'slice_size', 'prev_log_id', 'finger_print',
    ];

    public function file_slice() {
        return $this->belongsTo(FileSlice::class);
    }

    public function prev_log() {
        return $this->belongsTo(FileLog::class, 'prev_log_id');
    }
}
