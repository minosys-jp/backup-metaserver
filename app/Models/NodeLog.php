<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeLog extends Model
{
    use HasFactory;

    const public OPCODE_UPDATE = 1;
    const public OPCODE_RENAME = 2;
    const PUBLIC OPCODE_REMOVE = 3;

    protected $fillable = [
        'root_dir_id', 'node_id', 'old_parent_id', 'opcode', 'old_name', 'new_name', 'slice_offset', 'slice_size', 'slice_file', 'finger_print',
    ];

    public function node() {
        return $this->belongsTo(Node::class);
    }

    public function root_dir() {
        return $this->belongsTo(RootDir::class);
    }
}
