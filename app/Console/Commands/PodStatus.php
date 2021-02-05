<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disply the current status of the application stack';

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
      if(!got_podman()) die($this->error("The podman command was not found."));

      $app_name = env('APP_NAME');
      $output=null;
      $retval = null;
      $json = null;
      exec("podman pod exists {$app_name}", $outut, $retval);
      if($retval != 0){
        die("\033[33mWarning\033[0m The \033[1;37m{$app_name}\033[0m pod is not running.\n");
      }

      // $output = shell_exec('podman pod inspect LocalDomain --format "{{.Name}} {{.State}} {{.Created}}"');
      exec("podman pod inspect {$app_name}", $output, $retval);
      if($retval == 0){
        foreach($output as $line){
          $json .= $line;
        }
        $info = json_decode($json);
        $containers=[];
        $i = 0;
        foreach($info->Containers as $container){
          $containers[$i]['name'] = $container->Name;
          $containers[$i]['state'] = $container->State;
          $containers[$i]['id'] = $container->Id;
          $i++;
        }

        // Sort the containers by name so that the infra container doesnt show up in the middle of the list
        asort($containers);

        $state = ($info->State == "Running") ? "\033[1;32m{$info->State}\033[0m" : "\033[91m{$info->State}\033[0m" ;
        echo "\nPodman pod {$app_name} is currently {$state} with {$info->NumContainers} containers:\n";
        foreach($containers as $container){
          $state = ($container['state'] == "running") ? "\033[1;32m{$container['state']}\033[0m" : "\033[91m{$container['state']}\033[0m" ;
          echo "  - {$container['name']} is currently {$state}\n";
        }
        echo "\n";
      }
      else {
        foreach($output as $line){
          $this->line($line);
        }
        die($this->error("There was an error. Please check the logs above."));
      }

      return 0;
    }
}
