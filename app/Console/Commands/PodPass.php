<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodPass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:pass {cmd*} {--force} {--seed} {--pretend} (--class=?)';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pass artisan commands through to the PHP-FPM container in the pod.';

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
      $cmd = "";
      foreach($this->argument("cmd") as $arg){
        $cmd .= $arg." ";
      }
      foreach($this->options() as $k => $v){
        if(is_bool($v)){
          if($this->option($k)) $cmd .= "--{$k} ";
        }
        else {
          if($this->option($k)) $cmd .= "--{$k}={$v} ";
        }
      }
      $cmd = trim($cmd);

      $abbr = env('APP_ABBR');
      $output = null;
      $retval = null;

      // echo "podman exec -it {$abbr}-php php artisan {$cmd}\n";
      exec("podman exec -it {$abbr}-php php artisan {$cmd}", $output, $retval);
      foreach($output as $line){
        $this->line($line);
      }
      $this->newline();

      return 0;
    }
}
