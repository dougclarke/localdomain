# LocalDomain
Home on the Interwebs. Loosely inspired by the [Laravel Sail](https://laravel.com/docs/8.x/sail) project, LocalDomain is a PHP / Laravel based application stack with custom artisan commands for building and managing the entire stack using rootless, non-root containers with Podman rather than Docker.

The only thing that sudo / root is required for here is installing the systemd files generated from the pod globally if you so desire. The default actually creates systemd user files to start and stop the pod on login / logout.

Currently the stack is built / composed from the following container images:

- php:8-fpm-alpine
- nginxinc/nginx-unprivileged:1.18-alpine
- mysql:8.0
- redis:6-alpine
- owasp/zap2docker-weekly

## Required

Podman, PHP / Composer (but not really because you can resort to using ld-compose (see extra goodness below) or the PHP container directly :)

## Installation
`$ git clone https://github.com/dougclarke/localdomain.git ./some-app`

`$ cd some-app`

`$ composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist`

`$ cp .env-example .env && vi .env`

`$ php artisan pod:init --prod`

## Usage

`php artisan pod:up`

`php artisan pod:status`

`php artisan pod:top`

`php artisan pod:logs`

`php artisan pod:down`

`php artisan pod:reset`

`php artisan pod:generate systemd `

`php artisan pod:app migrate`

`php artisan pod:app migrate:fresh --seed`

`php artisan pod:cache`

`php artisan pod:cache --clear`


Upon startup the application will be available at http://localhost:8080

### OWASP Zed Attack Proxy (ZAP)

`php artisan pod:zap`

`php artisan pod:up --zap`

`php artisan pod:down --zap`

The ZAP container is available at http://localhost:8090

ZAP scan configuration files can be found in `stack/zap/conf/` and the ZAP reports are generated in the `stack/zap/data/` directory.


### Extra goodness

##### ld-compose
No PHP or composer on the host system? No problem! Check out the ld-compose bash script in stack/compose/ld-compos

Change into the fancy pants directory
`$ cd some-app/stack/compose`

Build the app container image
`$ ./ld-compose build`

Bring up the stack
`$ ./ld-compose up`

Run composer & npm install scripts then Laravel database migration
`$ ./ld-compose init`

##### Some Laravel jazz...
Sanctum/Fortify/Jetstream is currently included in the project with a special environment variable to quickly disable user registration without having to modify the Fortify config file.

Set the following variable in your application's .env file to disable new user registration

`ALLOW_REG=false`
