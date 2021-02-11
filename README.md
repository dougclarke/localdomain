# LocalDomain
Home on the Interwebs. Loosely inspired by the [Laravel Sail](https://laravel.com/docs/8.x/sail) project, LocalDomain is a PHP / Laravel based application stack with custom artisan commands for building and managing the entire stack using rootless, non-root containers with Podman rather than Docker.

The only thing that sudo / root is required for here is installing the systemd files generated from the pod if you so desire.

Currently the stack is built / composed from the following container images:

- php:8-fpm-alpine
- nginxinc/nginx-unprivileged:1.18-alpine
- mysql:8.0
- redis:6-alpine
- owasp/zap2docker-weekly

## Required

Podman, PHP

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

`php artisan pod:generate systemd `

`php artisan pod:pass migrate`


Upon startup the application will be available at http://localhost:8080

### OWASP Zed Attack Proxy (ZAP)

`php artisan pod:zap`

`php artisan pod:up --zap`

`php artisan pod:down --zap`

The ZAP container is available at http://localhost:8090


...
