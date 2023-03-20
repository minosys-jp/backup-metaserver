<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CheckPoint;
use App\Lib\CommonLib;

class CreateChheckPoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CreateCheckPoint';
    private $lib;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Check Point if any';

    public function __constructor(CommonLib $lib) {
        $this->lib = $lib;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();
            $cps = CheckPoint::where('flg_create', 0)->get();
            foreach ($cps as $cp) {
                $out = $cps->createYAML($cp->root_dir, $cp->when);
                $fname = $this->lib->datef() . $this->lib->randomName() . ".yml";

                $path = $this->lib->path($fname);
                while (file_exists($path)) {
                    $fname = $this->lib->datef() . $this->lib->randomName() . ".yml";
                    $path = $this->lib->path($fname);
                }
                Storage::disk('public')->put($this->lib->cache_path($fname), $out);
                $cp->flg_create = 1;
                $cp->cp_file_name = $fname;
                $cp->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollback();
        }
        return Command::SUCCESS;
    }
}
