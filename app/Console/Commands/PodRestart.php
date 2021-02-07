<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodRestart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:restart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart the pod';

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
      $this->call("pod:down");
      $this->call("pod:up");
      return 0;
    }
}
