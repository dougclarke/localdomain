<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:up {--zap : Start the stack including OWASP ZAP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the application stack';

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
      // Set up our vars
      //
      $uid = exec('id -u');
      $abbr = env('APP_ABBR', 'ld');
      $app_name = env('APP_NAME');
      $image_name = env('IMAGE_NAME', "alpine-{$abbr}-php");
      $output = null;
      $retval = null;
      $running = false;
      $image_name = "alpine-{$abbr}-php";
      // $zap_image = "owasp/zap2docker-bare:2.10.0";
      $zap_image = "owasp/zap2docker-weekly";

      $zap = ($this->option('zap')) ? true : false;

      //
      // Check for the podman executable
      //
      if(!got_podman()) die("The podman command was not found.\n");

      //
      // Make sure the pod is not already running before bringing it up.
      //
      exec('podman pod ps --format "{{.Name}}"', $output);

      foreach($output as $pod){
        if($pod == $app_name) $running = true;
      }

      if($running){
        die("\033[33mWarning\033[0m The \033[1;37m{$app_name}\033[0m podman application stack is already running.\n");
      }

      //
      // Make sure the app container image exists
      //
      exec("podman image exists {$image_name}", $output, $retval);
      if($retval != 0) {
        if($this->confirm("The {$image_name} app container image has not yet been build. Would you like to build the image now?")){
          $this->call('pod:build');
        }
        else {
          die("[\033[91mFAILED\033[0m] The stack can not be started because the app image {$image_name} does not exist.\n");
        }
      }

      $base_dir = base_path();
      $stack_dir = $base_dir."/stack";
      $vol_dir = $_SERVER['HOME']."/.local/share/containers/storage/volumes";
      $APP_KEY = env('APP_KEY', false);
      $DB_HOST = env('DB_HOST');
      $DB_PORT = env('DB_PORT');
      $DB_DATABASE = env('DB_DATABASE',false);
      $DB_USERNAME = env('DB_USERNAME',false);
      $DB_PASSWORD = env('DB_PASSWORD',false);
      $DB_ROOT_PASSWORD = $DB_PASSWORD.$DB_PASSWORD;
      $add_hosts = "";

      if(!$APP_KEY){
        $do_key = $this->choice("There is no APP_KEY set. Would you like to generate one now?", ['Yes','No'], 1);
        if($do_key){
          $this->call("key:generate");
        }
        else {
          die($this->error("You must configure your .env file including APP_KEY"));
        }
      }

      if(!$DB_DATABASE || !$DB_USERNAME || !$DB_PASSWORD){
        die($this->error("You need to configure your MySQL settings in your .env file."));
      }

      if($zap){
        $add_hosts = "--add-host {$abbr}-zap:127.0.0.1 --add-host {$abbr}-php:127.0.0.1 --add-host {$abbr}-mysql:127.0.0.1 --add-host {$abbr}-nginx:127.0.0.1 --add-host {$abbr}-redis:127.0.0.1";
      }
      else {
        $add_hosts = "--add-host {$abbr}-php:127.0.0.1 --add-host {$abbr}-mysql:127.0.0.1 --add-host {$abbr}-nginx:127.0.0.1 --add-host {$abbr}-redis:127.0.0.1";
      }
      $labels = "-l io.podman.compose.config-hash=123 -l io.podman.compose.project={$app_name} -l io.podman.compose.version=0.0.1 -l com.docker.compose.container-number=1";

      //
      // Starting up the podman container stack
      //
      if($zap){
        echo "Setting up the {$app_name} ZAP application stack...\n";
        exec("podman pod create --name={$app_name} --share net -p 8080:8080 -p 8090:8090", $output);

        // Create the ZAP data and conf dirs if they do not exist
        if(!is_dir(base_path()."/stack/zap/data")) mkdir(base_path()."/stack/zap/data", 0754, true);
        if(!is_dir(base_path()."/stack/zap/conf")) mkdir(base_path()."/stack/zap/conf", 0754, true);

        # OWASP ZAP container
        echo "Starting up the ZAP container [{$abbr}-zap]...\t";
        exec("podman run -u root --name={$abbr}-zap -d --pod={$app_name} \
          --mount type=bind,source={$stack_dir}/zap/data,destination=/zap/wrk \
          --mount type=bind,source={$stack_dir}/zap/conf,destination=/zap/conf \
          {$add_hosts} \
          {$labels} \
          -l com.docker.compose.service={$abbr}-zap \
          {$zap_image} zap.sh -daemon -host 0.0.0.0 -port 8090", $output);
        echo "[\033[1;32mDONE\033[0m]\n";
      }
      else {
        echo "Setting up the {$app_name} application stack...\n";
        exec("podman pod create --name={$app_name} --share net -p 8080:8080", $output);
      }

      # MySQL and Redis volumes
      echo "Setting up the {$app_name} MySQL and Redis volumes...\t";
      exec("podman volume inspect {$app_name}_mysqldata || podman volume create {$app_name}_mysqldata", $output);
      exec("podman volume inspect {$app_name}_redisdata || podman volume create {$app_name}_redisdata", $output);
      echo "[\033[1;32mDONE\033[0m]\n";

      # MySQL container
      echo "Starting up the MySQL container [{$abbr}-mysql]...\t";
      if(!is_dir(base_path()."/stack/mysql/db")) mkdir(base_path()."/stack/mysql/db", 0754, true);
      if(!is_dir(base_path()."/stack/mysql/initdb.d")) mkdir(base_path()."/stack/mysql/initdb.d", 0754, true);
      exec("podman run --name={$abbr}-mysql -d --pod={$app_name} \
        -e MYSQL_DATABASE={$DB_DATABASE} \
        -e MYSQL_ROOT_PASSWORD={$DB_ROOT_PASSWORD} \
        -e MYSQL_PASSWORD={$DB_PASSWORD} \
        -e MYSQL_USER={$DB_USERNAME} \
        --mount type=bind,source={$stack_dir}/mysql/initdb.d,destination=/docker-entrypoint-initdb.d \
        --mount type=bind,source={$vol_dir}/{$app_name}_mysqldata/_data,destination=/var/lib/mysql,bind-propagation=Z \
        {$add_hosts} \
        {$labels} -l com.docker.compose.service={$abbr}-mysql \
        --health-cmd='/bin/sh -c mysqladmin ping' \
        mysql:8.0", $output);
      echo "[\033[1;32mDONE\033[0m]\n";

      # Nginx rootless container
      echo "Starting up the Nginx container [{$abbr}-nginx]...\t";
      exec("podman run --name={$abbr}-nginx -d --pod={$app_name} \
        --mount type=bind,source={$base_dir},destination=/var/www \
        --mount type=bind,source={$stack_dir}/nginx/conf.d,destination=/etc/nginx/conf.d \
        {$add_hosts} \
        {$labels} \
        -l com.docker.compose.service={$abbr}-nginx \
        nginxinc/nginx-unprivileged:1.18-alpine", $output, $retval);
      if($retval != 0){
        foreach($output as $line){
          $this->line($line);
        }
        die($this->error("Failed to start the nginx container. Please see the above logs."));
      }
      else {
        echo "[\033[1;32mDONE\033[0m]\n";
      }

      # Redis container
      echo "Starting up the Redis container [{$abbr}-redis]...\t";
      exec("podman run --name={$abbr}-redis -d --pod={$app_name} \
        --mount type=bind,source={$vol_dir}/{$app_name}_redisdata/_data,destination=/data,bind-propagation=Z \
        {$add_hosts} \
        {$labels} -l com.docker.compose.service={$abbr}-redis\
        --health-cmd='/bin/sh -c redis-cli ping' \
        redis:6-alpine", $output);
      echo "[\033[1;32mDONE\033[0m]\n";

      # PHP-FPM container
      echo "Starting up the PHP-FPM container [{$abbr}-php]...\t";
      exec("podman run --name={$abbr}-php -d --pod={$app_name} --userns=host \
        --mount type=bind,source={$base_dir},destination=/var/www/ \
        {$add_hosts} \
        {$labels} -l com.docker.compose.service={$abbr}-php \
        -w /var/www/ \
        alpine-{$abbr}-php", $output);
      echo "[\033[1;32mDONE\033[0m]\n";

      $this->call("pod:status");

      if($zap){
        $zap_url = str_replace("8080", "8090", url('/'));
        echo "---\n";
        echo "The {$app_name} container stack is up and ready!\nCheck it out at ".url('/')."\n";
        echo "OWASP Zed Attack Proxy running on {$zap_url}\n";
        echo "\n";
      }
      else {
        echo "---\n";
        echo "The {$app_name} container stack is up and ready!\nCheck it out at ".url('/')."\n";
        echo "\n";
      }

      return 0;
    }
}
