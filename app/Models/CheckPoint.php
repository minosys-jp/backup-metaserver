<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CommonLib;
use Carbon\Carbon;

class CheckPoint extends Model
{
    use HasFactory;
    private $lib;

    protected $fillable = [
        'root_dir_id', 'flg_create', 'cp_file_name', 'description', 'when',
    ];

    protected $casts = [
        'when' => 'datetime',
    ];

    public __construct(CommonLib $lib) {
        $this->lib = $lib;
    }

    // create a file-slice YAML
    public function createYAML(RootDir $root_dir, string $when) {
        $out = "slice:1.0\n";
        $out .= "created_at:\"" . $this->lib->datef() . "\"\n";
        $out .= "files:\n";

        try {
            DB::beginTransaction();
            $files = NodeLog::join('nodes', 'nodes.id', 'node_id')
                ->where('flg_dir', Node::FLG_FILE)
                ->where('node_logs.created_at', '<=', $when)
                ->where('opcode', Node::OPCODE_UPDATE)
                ->wherNotIn('node_logs.id', function($q) use ($when) {
                    $q->where('opcode', 3)->where('created_at', '<=', $when);
                })
                ->select('nodes.id as id', 'slice_offset', DB::raw('max(id) as maxid'))
                ->groupBy(['nodes.id', 'slice_offset'])
                ->orderBy('nodes.id')
                ->orderBy('slice_offset')
                ->get();
            $node_id = null;
            $flg_ignore = FALSE;
            foreach ($files as $file) {
                if ($files->id !== $node_id) {
                    $out .="  - " . $this->get_filename($file->id, $when) . "\n";
                    $node_id = $files->id;
                    $flg_ignore = FALSE;
                }
                if ($flg_ignore) {
                    $nlog = NodeLog::find($file->maxid);
                    $out .= "    - " . $nlog->slice_offset . ":" . $nlog->slice_size . $nlog->slice_file . ":" . $nlog->finger_print . "\n";
                    $flg_ignore = ($nlog->slice_size < 2 * 1024 * 1024);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollback();
            return null;
        }
        return $out;
    }

    private function get_filename($node_id, $when) {
        $ar = [$this->get_nodename($node_id, $when)];
        $node = Node::find($node_id);
        $parent_id = $this->get_parent_node($node_id, $when);
        while (!is_null(($parent_id)) {
            $ar[] = $this->get_nodename($parent_id, $when);
            $node = Node::find($parent_id);
        } 
        $root_dir = RootDir::find($node->root_dir_id);
        $ar[] = $root_dir->name;
        $ar = array_reverse($ar);
        return implode('/', $ar);
    }

    private function get_parent_node($node_id, $when) {
       $maxid =  NodeLog::where('node_id', $node_id)->where('created_at', '<=', $when)
            ->where('opcode', NodeLog::OPCODE_RENAME)->max('id');
       if (is_null($maxid) {
           $node = Node::find($node_id);
           return $node->parent_id;
       } else {
           $nlog = NodeLog::find($maxid);
           return $nlog->old_parent_id;
       }
    }

    private function get_nodename($node_id, $when) {
        $minid = NodeLog::where('created_at', '>=', $when)
            ->where('opcode', NodeLog::OPCODE_RENAME)
            ->where('node_id', $node_id)
            ->min('id');
        if ($minid !== null) {
            $nlog = NodeLog::find($minid);
            $fname = $nlog->old_name;
        } else {
            $node = Node::find($node_id);
            $fname = $node->name;
        }
        return $fname;
    }
}
