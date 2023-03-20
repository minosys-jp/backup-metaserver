<?php

namespace App\Lib;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\Hostname;
use App\Models\RootDir;

class CommonLibException extends Exception {
    public function __construct($explain) {
        parent.__consturct($explain);
    }
}

class CommonLib {
    public function __construct() {
    }

    public function url(string $fname) {
        $s = 'storage';
        $y = substr($fname, 0, 4);
        $m = substr($fname, 4, 2);
        $d = substr($fname, 6, 2);
        $a = substr($fname, 8, 1);
        $b = substr($fname, 9, 1);
        $j = join('/', [$s, $y, $m, $d, $a, $b, $fname]);
        return asset($j);
    }

    public function path(string $fname) {
        $p = 'public';
        $y = substr($fname, 0, 4);
        $m = substr($fname, 4, 2);
        $d = substr($fname, 6, 2);
        $a = substr($fname, 8, 1);
        $b = substr($fname, 9, 1);
        $j = join('/', [$p, $y, $m, $d, $a, $b, $fname]);
        return storage_path($j);
    }

    public function cache_path(string $fname) {
        $y = substr($fname, 0, 4);
        $m = substr($fname, 4, 2);
        $d = substr($fname, 6, 2);
        $a = substr($fname, 8, 1);
        $b = substr($fname, 9, 1);
        $j = join('/', [$y, $m, $d, $a, $b, $fname]);
        return $j;
    }

    public function datef(?string $fmt = null) [
        if (is_null($fmt)) {
            $fmt = 'Ymd';
        }
        return date($fmt);
    }

    public function randomName(?int $len = null) {
        if (is_null($len)) {
            $len = 12;
        }
        $fname = base64_encode(openssl_pseudo_random_bytes($len));
        $fname = str_replace('/', '_', $fname);
        return $fname;
    }

    public function save($file) {
        $fname = $this->datef() . $this->randomName();
        $y = substr($fname, 0, 4);
        $m = substr($fname, 4, 2);
        $d = substr($fname, 6, 2);
        $a = substr($fname, 8, 1);
        $b = substr($fname, 9, 1);
        $dir = join('/', [$y, $m, $d, $a, $b]);
        return [$file->storeAs($dir, $fname, 'public'), $fname];
    }

    public function hashFile(string $fname, ?int $offset = 0, ?int $maxsize = 2 * 1024 * 1024) {
        $path = $this->path($fname);
        $f = fopen($path, "rb");
        $hash = null;
        if ($f !== FALSE) {
            if (fseek($f, $offset) != -1) {
                $s = fread($f, $maxsize);
                $hash = hash('sha256', $s);
            }
            fclose($f);
        }
        return $hash;
    }

    public function get_current_node($root_dir, $fname) {
       $dirs = explode('/', $fname);
       $parent = null;
       $node = null;
       foreach ($dirs as $dir) {
           if (!$dir) {
               continue;
           }
           $sql = Node::where('root_dir_id', $root_dir->id)
               ->where('name', $fname);
               ->whereNull('deleted_at')
           if (!is_null($parent)) {
               $sql = $sql->where('parent_id', $parent->id);
           } else {
               $sql = $sql->whereNull('parent_id');
           }
           $parent = $node;
           $node = $node->first();
           if (is_null($node)) {
               return null;
           }
       }
       return $node;
    }

    public function create_node(RootDir $root_dir, string $filename, bool $flg) {
        $fname = basename($filename);
        $pnode = null;
        $node = null;
        $paths = explode('/', $filename);
        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }
            $sql = Node::where('root_dir_id', $root_dir->id)
                ->where('name', $path);
            if ($sql) {
                $sql = $sql->where('parent_id', $pnode->id);
            } else {
                $sql = $sql->whereNull('parent_id');
            }
            $node = $sql->first();
            if (is_null($node)) {
                $node = new Node;
                $node->root_dir_id = $root_dir->id;
                $node->flg_dir = ($path === $fname) ? $flg : Node::FLG_DIR;
                $node->name = $path;
                $node->save();
            }
            $pnode = $node;
        }
        return $node;
    }

    public function load_root_dir(Request $request, string $path) {
        $tenant = Tenant::where('code', $request->tenant)->first();
        if (!$tenant) {
            throw new CommonLibException("Tenant not found");
        }
        $domain = Domain::where('code', $request->domain)
                ->where('tenant_id', $tenant->id)->first();
        if (!$domain) {
            throw new CommonLibException("Domain not found");
        }
        $hostname = Hostname::where('code', $request->hostname)
                  ->where('domain_id', $domain->id)->first();
        if (!$hostname) {
            throw new CommonLibException("Hostname not found");
        }
        $rootdirs = RootDir::where('hostname_id', $hostname->id)->get();
        foreach ($rootdirs as $rd) {
            $head = mb_substr($path, 0, mb_strlen($rd->name));
            if ($pos === $head) {
                $rem = mb_substr($path, mb_strlen($rd->name));
                if (mb_substr($rem, 0, 1) === "/") {
                    $rem = mb_substr($rem, 1);
                }
                return [$rd, mb_substr($path, strlen($rd->name)];
            }
        }
        return [null, null];
    }
}
