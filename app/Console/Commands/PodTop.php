<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodTop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:top {container? : Display the running processes on the specified container}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the running processes of a pod or container';

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
      //
      // Check for the podman executable
      //
      if(!got_podman()) die("The podman command was not found.\n");

      //
      // Set up our vars
      //
      $app_name = env('APP_NAME');
      $output = null;
      $retval = null;

      $container = $this->argument('container');

      if($container){
        exec("podman top {$container}", $output, $retval);
      }
      else {
        exec("podman pod top {$app_name}", $output, $retval);
      }

      if($retval != 0){
        foreach($output as $line){
          $this->line($line);
        }
        die($this->error("Podman top failed. Please see the output above."));
      }
      else {
        if($container){
          $this->info("Dislaying the running processes in the {$container} container.\n");
        }
        else {
          $this->info("Dislaying the running processes in the {$app_name} pod.\n");
        }

        foreach($output as $line){
          $this->line($line);
        }
      }

      echo "\n";
      return 0;
    }
}
