<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:cache {--clear : Clears the cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache (or clear) the app config, routes and views';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      if($this->option("clear")){
        $this->call("optimize:clear");
        $this->call("config:clear");
        $this->call("route:clear");
        $this->call("view:clear");
        $this->info("All clear!");
      }
      else {
        $this->call("optimize");
        $this->call("config:cache");
        $this->call("route:cache");
        $this->call("view:cache");
        $this->info("All cached up!");
      }
      return 0;
    }
}
