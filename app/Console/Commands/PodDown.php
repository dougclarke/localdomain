<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodDown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:down {--zap : Bring down the stack including OWASP ZAP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bring down the application stack';

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
      $abbr = env('APP_ABBR', 'ld');
      $app_name = env('APP_NAME');
      $output = null;
      $running = false;

      if(!got_podman()) die("The podman command was not found.\n");

      //
      // Make sure the pod is actually running before shutting it down.
      //
      exec('podman pod ps --format "{{.Name}}"', $output);

      foreach($output as $pod){
        if($pod == $app_name) $running = true;
      }

      if(!$running){
        die("\033[33mFailed\033[0m The \033[1;37m{$app_name}\033[0m podman stack is not running.\n");
      }

      //
      // Bring down the podman container stack
      //
      echo "Stopping the {$app_name} containers...\t";
      if($this->option('zap')) exec("podman stop -t=1 {$abbr}-zap", $output);
      exec("podman stop -t=1 {$abbr}-mysql", $output);
      exec("podman stop -t=1 {$abbr}-nginx", $output);
      exec("podman stop -t=1 {$abbr}-redis", $output);
      exec("podman stop -t=1 {$abbr}-php", $output);
      if($this->option('zap')) exec("podman rm {$abbr}-zap", $output);
      exec("podman rm {$abbr}-mysql", $output);
      exec("podman rm {$abbr}-nginx", $output);
      exec("podman rm {$abbr}-redis", $output);
      exec("podman rm {$abbr}-php", $output);
      echo "[\033[1;32mDONE\033[0m]\n";
      echo "Stopping the {$app_name} pod...\t";
      exec("podman pod rm {$app_name}", $output);
      echo "[\033[1;32mDONE\033[0m]\n";
      return 0;
    }
}
