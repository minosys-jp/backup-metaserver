<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\CommonLib;
use App\Lib\CommonLibException;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\Hostname;
use App\Models\RootDir;
use App\Models\Node;
use App\Models\NodeLog;
use Carbon\Carbon;

class ApiController extends Controller
{
    private $lib;

    public function __construct(CommonLib $lib) {
        $this->lib = $lib;
    }

    private function check_hostname(Request $request) {
        $tenant = Tenant::where('code', $request->tenant)->first();
        if (is_null($tenant)) {
            throw new CommonLibException("Tenant not found");
        }
        $domain = Domain::where('code', $request->domain)
            ->where('tenant_id', $tenant->id)->first();
        if (is_null($domain)) {
            throw new CommonLibException("Domain not found");
        }
        $hostname = Hostname::where('code', $request->hostname)
            ->where('domain_id', $domain->id)->first();
        if (is_null($hostname)) {
            throw new CommonLibException("Hostname not found");
        }
        return $hostname;
    }

    //
    public function file_fp(Request $request) {
        try {
            $hostname = $this->check_hostname($request);
            $root_dir = RootDir::where('name', $request->root_dir)->where('hostname_id', $hostname->id)->first();
            if (!$root_dir) {
                throw new CommonLibException("Root dir not found");
            }
            $r = [];
            foreach ($request->files as $file) {
                $node = $this->lib->get_current_node($root_dir, $file);
                if (is_null($node)) {
                    $r[] = [ 'file' => $file, 'slices' => null ];
                } else {
                    $unodes = NodeLog::join('nodes', 'nodes.id', 'node_id')
                        ->where('flg_dir', Node::FLG_FILE)
                        ->whereNull('nodes.deleted_at')
                        ->where('node_id', $node->id)
                        ->where('opcode', NodeLog::OPCODE_UPDATE)
                        ->select('slice_offset', DB::raw("max('node_logs.id') as id"))
                        ->groupBy('slice_offset')
                        ->orderBy('node_id')
                        ->orderBy('slice_offset')
                        ->get();
                    $ar = [];
                    foreach ($unodes as $unode) {
                        $maxnode = NodeLog::find($unode->id);
                        $ar[] = [
                            'offset' => $maxnode->slice_offset,
                            'size' => $maxnode->slice_size,
                            'file' => $maxnode->slice_file,
                        ];
                    } 
                    $r[] = [
                        'file' => $file,
                        'slices' => $ar,
                    ];
                }
            }
            return response()->json([TRUE, $r]);
        } catch (CommonLibException $e) {
            $r = [FALSE, $e->getMessage()];
            return response()->json($r);
        }
    }

    public function move_file(Request $request) {
        try {
            DB::beginTransaction();
            $done = FALSE;
            $hostname = $this->check_hostname($request);
            $root_dir = RootDir::where('hostname_id', $hostname->id)
                ->where('name', $request->root_dir)->first();
            if (is_null($root_dir)) {
                throw new CommonLibException("Root dir not found");
            }
            $old_node = $this->lib->get_current_node($root_dir, $request->old_name);
            if (is_null($old_node)) {
                throw new CommonLibException("File not found");
            }
            if ($old_node->flg_dir == Node::FLG_DIR) {
                $new_node = $this->lib->get_current_node($root_dir, $request->new_name);
                if (!is_null($new_node)) {
                    throw new CommonLibException("Dir already defined");
                }
                $pname = dirname($request->new_name);
                $pnode = $this->lib->create_node($root_dir, $pname, Node::FLG_DIR);
                $fname = basename($request->new_name);
                $node = new Node;
                $node->root_dir_id = $root_dir->id;
                $node->parent_id = $pnode ? $pnode->id : null;
                $node->flg_dir = $old_node->flg_dir;
                $node->name = $fname;
                $node->save();

            } else {
                $new_node = $this->lib->get_current_node($root_dir, $request->new_name);
                if (is_null($new_node)) {
                    $pname = dirname($request->new_name);
                    $pnode = $this->lib->get_current_node($root_dir, $pname);
                    $node = new Node;
                    $node->parent_id = $pnode ? $pnode->id : null;
                    $node->flg_dir = $old_node->flg_dir;
                    $node->name = basename($request->new_name);
                    $node->save();
                } else if ($new_node->flg_dir === Node::FLG_DIR) {
                    $nlog = new NodeLog;
                    $nlog->opcode = NodeLog::OPCODE_RENAME;
                    $nlog->old_name = $old_node->name;
                    $nlog->new_name = $old_node->name;
                    $nlog->old_parent_id = $old_node->parent_id;
                    $nlog->root_dir_id = $root_dir->id;
                    $nlog->node_id = $old_node->id;
                    $nlog->save();

                    $old_node->parent_id = $new_node->id;   
                    $old_node->save();
                    $done = TRUE;
                } else {
                    // remove the current file
                    $new_node->deleted_at = Carbon::now();
                    $new_node->save();
                    $nlog = new NodeLog;
                    $nlog->opcode = NodeLog::OPCODE_REMOVE;
                    $nlog->root_dir_id = $new_node->root_dir_id;
                    $nlog->node_id = $new_node->id;
                    $nlog->save();

                    $node = $this->lib->create_node($root_dir, $request->new_name);
                    $node->save();
                }
            }

            if (!$done) {
                $nlog = new NodeLog;
                $nlog->opcode = NodeLog::OPCODE_RENAME;
                $nlog->old_name = $old_node->name;
                $nlog->new_name = $node->name;
                $nlog->root_dir_id = $root_dir->id;
                $nlog->old_parent_id = $old_node->parent_id;
                $nlog->node_id = $node->id;
                $nlog->save();
            }
            DB::commit();
            return response()->json([TRUE, null]);
        } catch (CommonLibException $e) {
            DB::rollback();
            $r = [FALSE, $e->getMessage()];
            return response()->json($r);
        }
    }

    public function remove_file(Request $request) {
        try {
            DB::beginTransaction();
            $hostname = $this->check_hostname($requests);
            $root_dir = RootDir::where('hostname_id', $hostname->id)
                ->where('name', $request->root_dir)->first();
            if (is_null($root_dir)) {
                throw new CommonLibException("Root dir not found");
            }
            foreach ($request->files as $file) {
                $node = $this->lib->get_current_node($root_dir, $file);
                if (is_null($node)) {
                    throw new CommonLibException("File not found");
                }
                if ($node->flg_dir === Node::FLG_DIR) {
                    // filled directory cant be removed
                    if (Node::where('parent_id', $node->id)
                            ->whereNull('deleted_at')->exists()) {
                        throw new CommonLibException("Dir not empty");
                    } 
                }
                $node->deleted_at = Carbon::now();
                $node->save();

                $nlog = new NodeLog;
                $nlog->opcode = NodeLog::OPCODE_REMOVE;
                $nlog->root_dir_id = $root_dir->id;
                $nlog->node_id = $node->id;
            }
            DB::commit();
            return response()->json([TRUE, null]);
        } catch (CommonLibException $e) {
            DB::rollback();
            $r = [FALSE, $e->getMessage()];
            return response()->json($r);
        }
    }

    public function upload_slice(Request $request) {
        try {
            DB::beginTransaction();
            $tenant = Tenant::where('code', $request->tenant)->first();
            if (is_null($tenant)) {
                throw new CommonLibException("Tenant not found");
            }
            $domain = Domain::where('code', $request->domain)
                ->where('tenant_id', $tenant->id)->first();
            if (is_null($domain)) {
                throw new CommonLibException("Domain not found");
            }
            $hostname = Hostname::where('code', $request->hostname)
                ->where('domain_id', $domain->id)->first();
            if (is_null($hostname)) {
                $hostname = new Hostname;
                $hostname->domain_id = $domain->id;
                $hostname->code = $request->hostname;
                $hostname->name = strtoupper($request->hostname);
                $hostname->save();
            }
            $root_dir = RootDir::where('name', $request->root_dir)
                ->where('hostname_id', $hostname->id)->first();
            if (is_null($root_dir)) {
                $root_dir = new RootDir;
                $root_dir->name = $request->root_dir;
                $root_dir->hostname_id = $hostname->id;
                $root_dir->save();
            }

            if ($request->hasFile('files')) {
                $count = 0;
                foreach ($request->file('files') as $file) {
                    $path = $request->path[$count];
                    $offset = $request->offset[$count];
                    $size = $file->size;
                    list($fpath, $fname) = $sname = $this->lib->save($file);
                    $node = $this->lib->get_current_node($root_dir, $path);
                    if (is_null($node)) {
                        $node = $this->lib->create_node($root_dir, $path, Node::FLG_FILE);
                    }
                    $nlog = new NodeLog;
                    $nlog->opcode = NodeLog::OPCODE_UPDATE;
                    $nlog->root_dir_id = $root_dir->id;
                    $nlog->node_id = $node->id;
                    $nlog->slice_offset = $offset;
                    $nlog->slice_size = $size;
                    $nlog->finger_print = $this->lib->hashFile($fpath);
                    $nlog->save();
                    
                    $count++;
                }
            }
            DB::commit();
            return response()->json([TRUE, null]);
        } catch (CommonLibException $e) {
            DB::rollback();
            $r = [FALSE, $e->getMessage()];
            return response()->json($r);
        }
    }
}
