<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:generate
                              {type=null : Specify systemd to generate systemd startup files or kube for a Kubernetes service files}
                              {--user : Install systemd user files. The stack will boot on user login rather than on system boot}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate systemd or Kubernetes files for the application stack';

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

      $app_name = env('APP_NAME');
      $user = exec('id -un');
      $home = (is_dir($_SERVER['HOME'])) ? $_SERVER['HOME'] : "/home/{$user}";

      if(!$type || !preg_match('/[systemd|kube]/', $type)){
        $type = $this->choice('Generate structured data for systemd or kubernetes?', ['systemd', 'kube'], 0, $maxAttempts = null, $allowMultipleSelections = false);
      }

      if($type == "systemd"){
        echo "Generating the systemd unit files for {$app_name}...\t";
        exec("podman generate systemd -f --name {$app_name}", $output);
        $pod_service_file = $output[0];

        $inspect = $this->choice("Would you like to inspect the generated systemd unit files before installing?",["yes","no"], 0);
        if($inspect == "yes"){
          foreach($output as $file){
            echo "Inspecting {$file}...\n";
            shell_exec("less {$file}");
            echo "---\n";
          }
        }
        $install = $this->choice("Would you like to install the generated systemd unit files?",["yes","no"], 0);

        //
        // Install the systemd service files
        //
        if($install == "yes"){
          $service_files = null;
          foreach($output as $file){
            $service_files .= $service_files." ".$file;
          }
          $service_files = ltrim($service_files);

          //
          // user level systemd startup install. Launches on user login.
          //
          if($this->option('user')){
            $user_dir = "{$home}/.config/systemd/user";
            if(!is_dir($user_dir)) mkdir($user_dir,0755,true);
            exec("sudo cp {$service_files} {$user_dir}");
            exec("systemctl enable {$pod_service_file}");
          }

          //
          // system-wide systemd install requires sudo. Launches on system startup.
          //
          else {
            exec("sudo cp {$service_files} /etc/systemd/system");
            exec("sudo systemctl enable {$pod_service_file}");
          }
        }

        //
        // Do not install the systemd service files
        //
        else {
          $base_dir = base_path();
          exit($this->info("Generator complete! The service files were not installed and can be located in the {$base_dir} directory"));
        }
      }
      elseif($type == "kube"){
        $retval = null;
        echo "Generating Kubernetes YAML based on {$app_name}...\t";
        exec("podman generate kube -f -s {$app_name} 2>&1", $output, $retval);
        if($retval != 0){
          foreach($output as $line){
            $this->line($line);
          }
          die($this->error("Could not generate the Kubernetes YAML file."));
        }
      }
      else {
        die($this->error("Invalid generator type."));
      }


      echo "[\033[1;32mDONE\033[0m]\n";


        return 0;
    }
}
