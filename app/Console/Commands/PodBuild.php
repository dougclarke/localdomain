<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:build {--rootless : Build the PHP-FPM container in rootless, non-root mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the application stack containers using podman';

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

      $abbr = env('APP_ABBR', 'ld');
      $app_name = env('APP_NAME');
      $image_name = env('IMAGE_NAME', "alpine-{$abbr}-php");
      $base_dir = base_path();
      $stack_dir = $base_dir."/stack";
      $output = null;
      $retval = null;
      $uid = exec('id -u');
      $user = exec('id -un');

      if($this->option('rootless')){
        echo "Setting up rootless permissions on the storage volume and bootstrap cache...";
        exec("podman unshare chown -R ${uid}:${uid} {$base_dir}/storage/*", $output, $retval);
        if($retval != 0){
          foreach($output as $line){
            $this->line($line);
          }
          $this->warning("Failed to set the storage permissions. Please see the output above for more information.");
        }
        exec("podman unshare chown -R ${uid}:${uid} {$base_dir}/bootstrap/cache", $output, $retval);
        if($retval != 0){
          foreach($output as $line){
            $this->line($line);
          }
          $this->warning("Failed to set the bootstrap cache directory permissions. Please see the output above for more information.");
        }
        echo "Building the \033[1;37mrootless\033[0m {$app_name} {$image_name} image... this may take a while.\t";
        exec("podman build -t {$image_name} -f {$stack_dir}/php-fpm/Containerfile-rootless --build-arg=uid={$uid} --build-arg=user={$user} {$stack_dir}/php-fpm", $output, $retval);
      }
      else {
        echo "Building the {$app_name} {$image_name} image... this may take a while.\t";
        exec("podman build -t {$image_name} -f {$stack_dir}/php-fpm/Containerfile --userns='host' {$stack_dir}/php-fpm", $output, $retval);
      }

      if($retval != 0){
        foreach($output as $line){
          $this->line($line);
        }
        die($this->error("Could not build the image. Please see the output above for more information."));
      }
      else {
        echo "[\033[1;32mDONE\033[0m]\n";
      }

      return 0;
    }
}
