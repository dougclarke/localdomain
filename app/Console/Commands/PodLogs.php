<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:logs {container? : Display the specified container logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the application or specified container logs';

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
      $json = null;

      if(!$this->argument('container')){
        // get the containers for the app_name pod
        exec("podman pod inspect {$app_name}", $output, $retval);
        if($retval == 0){
          foreach($output as $line){
            $json .= $line;
          }
          $info = json_decode($json);
          $containers=[];
          $i = 0;
          foreach($info->Containers as $container){
            if(!stristr($container->Name,"infra")){
              $containers[$i] = $container->Name;
              $i++;
            }
          }
        }
        else {
          die($this-error("Failed to inspect the {$app_name} pod"));
        }
      }
      else {
        $containers[] = $this->argument('container');
      }

      // foreach container as c podman logs $c
      foreach($containers as $container){
        $output = null;
        $this->info("\nDisplaying logs for [{$container}]...");
        exec("podman logs {$container}", $output, $retval);
        if($retval == 0){
          foreach($output as $line){
            $this->line($line);
          }
        }
      }

      echo "\n";

      return 0;
    }
}
