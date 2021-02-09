<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PodInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pod:init {--prod}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the podman based application stack';

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
      // check for podman, composer and npm
      if(!got_composer()) die($this->error("The php composer command was not found."));
      if(!got_npm()) die($this->error("The nodejs npm command was not found."));
      if(!got_podman()) die($this->error("The podman command was not found."));

      $prod = ($this->option('prod')) ? $this->option('prod') : false;

      if(!is_file(base_dir()."/.env")){
        // ...for now, just die.
        die($this->error("You MUST create and configure your .env file before you can continue!"));

        //
        // TODO: generate a .env file
        //
        $this->error("You MUST create and configure your .env file before you can continue!");
        $choice = $this->choice("Would you like to copy and edit the default .env file now?", ["Yes", "No"], 0);
        if(!$choice){
          die($this->error("Can not initialize the app without an .env file"));
        }
        else {
          copy(base_dir()."/.env-example", base_dir()."/.env");
          $this->call("key:generate");

          // step through some options and spit out a final .env file
        }
      }

      $install = "";

      if($prod){
        $install = "composer install --optimize-autoloader --no-dev && \
          npm install --production && \
          npm run prod";
      }
      else {
        $install = "composer install && \
          npm install && \
          npm run dev";
      }

      exec($install, $output, $retval);
      foreach($outut as $line){
        $this->line($line);
      }
      if($retval != 0){
        die($this->error("There was a problem installing dependencies"));
      }

      if($prod) $this->call("pod:build",["rootless" => true]);
      else $this->call("pod:build");

      $this->call("pod:up");

      if($prod){
        $this->call("pod:app migrate");
        $this->call("pod:cache");
      }
      else {
        $this->call("pod:app migrate:fresh --seed");
      }

      $this->call("pod:restart");

      return 0;
    }
}
