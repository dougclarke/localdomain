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
    protected $signature = 'pod:up
                              {--zap : Start the stack including OWASP ZAP}
                              {--webswing : Start ZAP with the WebSwing UI}
                              {--mailhog : Start the mailhog SMTP container}';

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
      $pod_port = env('POD_PORT', 8000);
      $output = null;
      $retval = null;
      $running = false;
      $err = false;
      $image_name = "alpine-{$abbr}-php";
      // $zap_image = "owasp/zap2docker-bare:2.10.0";
      $zap_image = "owasp/zap2docker-weekly";

      $zap = ($this->option('zap')) ? true : false;
      $webswing = ($this->option('webswing')) ? true : false;
      if($webswing) $zap = true;
      $mailhog = ($this->option('mailhog')) ? true : false;

      $env = env('APP_ENV');
      $mail_host = env('MAIL_HOST');
      $mail_port = env('MAIL_PORT');
      $mail_user = env('MAIL_USERNAME');
      $mail_pass = env('MAIL_PASSWORD');
      if(preg_match("/local|dev|development/i",$env)){
        if($mail_host == "localhost" && $mail_port = 1025 && is_null($mail_user) && is_null($mail_pass)){
          $mailhog = true;
        }
      }

      $mailhog_ports = ($mailhog) ? "-p 1025:1025 -p 8025:8025" : "";

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
        if($this->confirm("The {$image_name} app container image has not yet been built. Would you like to build the image now?")){
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

      $add_zap = ($zap) ? "--add-host {$abbr}-zap:127.0.0.1" : "";
      $add_mailhog = ($mailhog) ? "--add-host {$abbr}-mailhog:127.0.0.1" : "";

      $add_hosts = "{$add_zap} {$add_mailhog} --add-host {$abbr}-php:127.0.0.1 --add-host {$abbr}-mysql:127.0.0.1 --add-host {$abbr}-nginx:127.0.0.1 --add-host {$abbr}-redis:127.0.0.1";
      $labels = "-l io.podman.compose.config-hash=123 -l io.podman.compose.project={$app_name} -l io.podman.compose.version=0.0.1 -l com.docker.compose.container-number=1";

      //
      // Starting up the podman container stack
      //
      if($zap){
        echo "Setting up the {$app_name} ZAP application stack...\n";
        exec("podman pod create --name={$app_name} --share net -p {$pod_port}:{$pod_port} -p 8080:8080 -p 8090:8090 {$mailhog_ports}", $output);

        // Create the ZAP data and conf dirs if they do not exist
        if(!is_dir(base_path()."/stack/zap/data")) mkdir(base_path()."/stack/zap/data", 0754, true);
        if(!is_dir(base_path()."/stack/zap/conf")) mkdir(base_path()."/stack/zap/conf", 0754, true);

        #
        # OWASP ZAP container
        #
        echo "Starting up the ZAP container [{$abbr}-zap]...\t";

        if($webswing){
          exec("podman run -u root --name={$abbr}-zap -d --pod={$app_name} \
            --mount type=bind,source={$stack_dir}/zap/data,destination=/zap/wrk \
            --mount type=bind,source={$stack_dir}/zap/conf,destination=/zap/conf \
            {$add_hosts} \
            {$labels} \
            -l com.docker.compose.service={$abbr}-zap \
            {$zap_image} zap-webswing.sh", $output, $retval);
        }
        else {
          exec("podman run -u root --name={$abbr}-zap -d --pod={$app_name} \
            --mount type=bind,source={$stack_dir}/zap/data,destination=/zap/wrk \
            --mount type=bind,source={$stack_dir}/zap/conf,destination=/zap/conf \
            {$add_hosts} \
            {$labels} \
            -l com.docker.compose.service={$abbr}-zap \
            {$zap_image} zap.sh -daemon -host 0.0.0.0 -port 8090 -config api.addrs.addr.name=.* -config api.addrs.addr.regex=true", $output, $retval);
        }

        if($retval != 0){
          $error['name'] = "{$abbr}-zap";
          $error['log'] = $output;
          $err[] = $error;
          echo "[\033[1;31mFAILED\033[0m]\n";
        }
        else {
          echo "[\033[1;32mDONE\033[0m]\n";
        }
      }
      else {
        echo "Setting up the {$app_name} application stack...\n";
        exec("podman pod create --name={$app_name} --share net -p {$pod_port}:{$pod_port} {$mailhog_ports}", $output);
      }

      #
      # MySQL and Redis volumes
      #
      echo "Setting up the {$app_name} MySQL and Redis volumes...\t";
      exec("podman volume inspect {$app_name}_mysqldata || podman volume create {$app_name}_mysqldata", $output);
      exec("podman volume inspect {$app_name}_redisdata || podman volume create {$app_name}_redisdata", $output);
      echo "[\033[1;32mDONE\033[0m]\n";

      #
      # MySQL container
      #
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
        mysql:8.0", $output, $retval);
      if($retval != 0){
        $error['name'] = "{$abbr}-mysql";
        $error['log'] = $output;
        $err[] = $error;
        echo "[\033[1;31mFAILED\033[0m]\n";
      }
      else {
        echo "[\033[1;32mDONE\033[0m]\n";
      }

      #
      # Nginx rootless container
      #
      echo "Starting up the Nginx container [{$abbr}-nginx]...\t";

      $nginx_conf = file_get_contents("{$stack_dir}/nginx/conf.d/laravel_app.conf.template");
      $nginx_conf = str_replace('${POD_PORT}', $pod_port, $nginx_conf);
      file_put_contents("{$stack_dir}/nginx/conf.d/laravel_app.conf", $nginx_conf);

      exec("podman run --name={$abbr}-nginx -d --pod={$app_name} \
        --mount type=bind,source={$base_dir},destination=/var/www \
        --mount type=bind,source={$stack_dir}/nginx/conf.d,destination=/etc/nginx/conf.d \
        {$add_hosts} \
        {$labels} \
        -l com.docker.compose.service={$abbr}-nginx \
        nginxinc/nginx-unprivileged:1.18-alpine", $output, $retval);

      if($retval != 0){
        $error['name'] = "{$abbr}-nginx";
        $error['log'] = $output;
        $err[] = $error;
        echo "[\033[1;31mFAILED\033[0m]\n";
      }
      else {
        echo "[\033[1;32mDONE\033[0m]\n";
      }

      #
      # Redis container
      #
      echo "Starting up the Redis container [{$abbr}-redis]...\t";
      exec("podman run --name={$abbr}-redis -d --pod={$app_name} \
        --mount type=bind,source={$vol_dir}/{$app_name}_redisdata/_data,destination=/data,bind-propagation=Z \
        {$add_hosts} \
        {$labels} -l com.docker.compose.service={$abbr}-redis\
        --health-cmd='/bin/sh -c redis-cli ping' \
        redis:6-alpine", $output);
      // echo "[\033[1;32mDONE\033[0m]\n";
      if($retval != 0){
        $error['name'] = "{$abbr}-redis";
        $error['log'] = $output;
        $err[] = $error;
        echo "[\033[1;31mFAILED\033[0m]\n";
      }
      else {
        echo "[\033[1;32mDONE\033[0m]\n";
      }

      #
      # PHP-FPM container
      #
      echo "Starting up the PHP-FPM container [{$abbr}-php]...\t";
      exec("podman run --name={$abbr}-php -d --pod={$app_name} --userns=host \
        --mount type=bind,source={$base_dir},destination=/var/www/ \
        {$add_hosts} \
        {$labels} -l com.docker.compose.service={$abbr}-php \
        -w /var/www/ \
        alpine-{$abbr}-php", $output);
      // echo "[\033[1;32mDONE\033[0m]\n";
      if($retval != 0){
        $error['name'] = "{$abbr}-php";
        $error['log'] = $output;
        $err[] = $error;
        echo "[\033[1;31mFAILED\033[0m]\n";
      }
      else {
        echo "[\033[1;32mDONE\033[0m]\n";
      }

      #
      # Mailhog container
      #
      if($mailhog){
        echo "Starting up the MailHog container [{$abbr}-mailhog]...\t";
        exec("podman run --name={$abbr}-mailhog -d --pod={$app_name} \
          {$add_hosts} \
          {$labels} -l com.docker.compose.service={$abbr}-mailhog \
          mailhog/mailhog", $output, $retval);
        // echo "[\033[1;32mDONE\033[0m]\n";
        if($retval != 0){
          $error['name'] = "{$abbr}-mailhog";
          $error['log'] = $output;
          $err[] = $error;
          echo "[\033[1;31mFAILED\033[0m]\n";
        }
        else {
          echo "[\033[1;32mDONE\033[0m]\n";
        }
      }


      if(empty($err)){
        $this->call("pod:status");

        if($zap){
          $zap_port = ($webswing) ? "8080" : "8090";
          $zap_url = str_replace("8000", $zap_port, url('/'));
          echo "---\n";
          echo "The {$app_name} container stack is up and ready!\nCheck it out at ".url('/')."\n";
          if($webswing){
            echo "OWASP ZAP WebSwing UI running on {$zap_url}\n";
          }
          else {
            echo "OWASP Zed Attack Proxy running on {$zap_url}\n";
          }
          echo "\n";
        }
        else {
          echo "---\n";
          echo "The {$app_name} container stack is up and ready!\nCheck it out at ".url('/')."\n";
          echo "\n";
        }

        return 0;
      }
      else {
        foreach($err as $e){
          echo "\n";
          $this->error("{$e['name']} error log:");
          foreach($e['log'] as $l){
            $this->line($l);
          }
          echo "---\n";
        }

        return 2;
      }

    }
}
