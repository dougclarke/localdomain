<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodStop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alias of pod:down';

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
        return 0;
    }
}
