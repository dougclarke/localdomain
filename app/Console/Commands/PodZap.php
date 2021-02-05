<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodZap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:zap
                              {type=baseline : The type of scan to perform: [baseline], api or full}
                              {--api-type=openapi : The type of API scan to perform [openapi, soap, graphql]}
                              {--api-src=? : The file or URL for the API definition}
                              {--target="http://localhost:8080" : Target URL for the scan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the OWASP-ZAP application security tests';

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
      if(!got_podman()) die("The podman command was not found.\n");

      //
      // Set up our vars
      //
      $uid = exec('id -u');
      $abbr = env('APP_ABBR', 'ld');
      $app_name = env('APP_NAME');
      $app_url = env('APP_URL');
      // $image_name = "owasp/zap2docker-bare:2.10.0";
      $image_name = "owasp/zap2docker-weekly";
      $output = null;
      $retval = null;
      $running = false;

      $type = $this->argument('type');
      $api_type = $this->argument('api-type');
      $api_src = ($this->option('api-src')) ? $this->option('api-src') : null;
      $target = ($this->option('target')) ? $this->option('target') : $app_url;

      $base_dir = base_path();
      $stack_dir = $base_dir."/stack";
      $vol_dir = $_SERVER['HOME']."/.local/share/containers/storage/volumes";
      $add_hosts = "--add-host {$abbr}-php:127.0.0.1 --add-host {$abbr}-mysql:127.0.0.1 --add-host {$abbr}-nginx:127.0.0.1 --add-host {$abbr}-redis:127.0.0.1";
      $labels = "-l io.podman.compose.config-hash=123 -l io.podman.compose.project={$app_name} -l io.podman.compose.version=0.0.1 -l com.docker.compose.container-number=1";

      //
      // Make sure the pod is actually running before shutting it down.
      //
      exec('podman pod ps --format "{{.Name}}"', $output);

      foreach($output as $pod){
        if($pod == $app_name) $running = true;
      }

      //
      // Make sure the OWASP-ZAP image exists
      //
      exec("podman image exists {$image_name}", $output, $retval);
      if($retval != 0) {
        exec("podman pull {$image_name}", $output, $retval);
        if($retval != 0) {
          die($this->error("Could not pull the {$image_name} image.\n"));
        }
      }

      // if stack is Running: shut it down
      exec('podman pod ps --format "{{.Name}}"', $output);

      foreach($output as $pod){
        if($pod == $app_name) $running = true;
      }

      if($running){
        $this->info("Restarting the pod in ZAP mode :)");
        $this->call("pod:down");
      }

      \Artisan::call("pod:up",["--zap" => true]);

      //    run zap tests
      echo "Running ZAP {$type} scan...\n";
      $report = date("YmdHi")."-{$app_name}-".$type."-scan.html";

      // Full scan...
      if($type == "full"){
        $conf = "../conf/full-scan.conf";
        $cs = (!is_file($conf)) ? "-g" : "-c";
        exec("podman exec -u root {$abbr}-zap \
          zap-full-scan.py \
          {$cs} {$conf} \
          -t {$target} \
          -r {$report}", $output, $retval);
      }

      // API scan...
      elseif($type == "api"){
        $conf = "../conf/api-scan.conf";
        //'openapi', 'soap', or 'graphql'
        $cs = (!is_file($conf)) ? "-g" : "-c";
        exec("podman exec -u root {$abbr}-zap \
          zap-api-scan.py \
          {$cs} {$conf} \
          -t {$target} \
          -r {$report}", $output, $retval);
      }

      // Baseline scan...
      else {
        $conf = "../conf/baseline-scan.conf";
        $cs = (!is_file($conf)) ? "-g" : "-c";
        exec("podman exec -u root {$abbr}-zap \
          zap-baseline.py \
          {$cs} {$conf} \
          -t {$api_src} \
          -f {$api_type} \
          -r {$report}", $output, $retval);
      }

      \Artisan::call("pod:down", ["--zap" => true]);

      if($running) {
        echo "\nRestarting the {$app_name} application stack...";
        $this->call("pod:up");
      }

      $conf = str_replace("../", "zap/", $conf);

      echo "\n---\n";
      echo "ZAP config: {$conf}\n";
      echo "Report generated: zap/data/{$report}\n";

      return 0;
    }
}
