# LocalDomain
Home on the Interwebs. Loosely inspired by the [Laravel Sail](https://laravel.com/docs/8.x/sail) project, LocalDomain is a PHP / Laravel based application stack with custom artisan commands for managing the entire stack using Podman rather than Docker.

Currently the stack is built / composed from the following container images:

- php:8-fpm-alpine
- nginxinc/nginx-unprivileged:1.18-alpine
- mysql:8.0
- redis:6-alpine
- owasp/zap2docker-weekly

## Required

Podman, PHP

## Installation
`$ git clone {this-repo} ./some-app`

## Usage

`php artisan pod:build`

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
