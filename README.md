## About Laravel Tutorial Task App

 PHP 8.4 | Laravel 11.x | MySQL 8.0 | Docker Compose 3.8 
 
 Composer 2.8 | Node 22.21.1 | NPM 10.9.4

#### how to start docker
````
cd docker
docker-compose up -d --build
````
#### how to stop docker
````
cd docker
docker-compose down
````
#### Inside app web container
````
docker exec -it laravel_app bash
````
#### Set Up

Copy .env.example to .env and update the credentials as needed.

````
- composer install 
- supervisorctl start queue_worker
- npm install && npm run build // optional: for FE assets
- php artisan key:generate
- php artisan  migrate --seed
````

#### PHPStorm IDE
DB settings
````
host: 127.0.0.1
port: 3506
user: root
password: root
database: task
````
ID Plugin settings 
````
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:models --nowrite
````


#### Schickling MailCatcher Documentation:

MailCatcher is configured by setting the environment variable in your `.env` file:

For testing email functionality, we are using the Schickling MailCatcher.
It is accessible at `http://localhost:2080`.
This allows you to view emails sent by the application without needing a real email server.

#### Laravel Crontab Scheduler 

All scheduler commands are defined in `app/route/console.php` file.
To run the scheduler, make sure the cron service is running as `scheduler` Docker container.
`php artisan schedule:run` will be run every minute by cron inside that container.

log view
```
docker logs laravel_scheduler -f
```
#### RabbitMQ Information:

RabbitMQ is used for message queuing in the application.

It is accessible at `http://localhost:15672` with the following credentials:
```
Username: guest
Password: guest
```
#### Supervisord Information:

Supervisord is used to manage the background processes in the application.

Please check the `supervisord.conf` file for more information.

To manage the queue worker using Supervisor, open http://localhost:9001 with the following credentials:
```
Username: guest
Password: guest
```

#### API Documentation

For Authentication, Laravel Sanctum is used.

Current default expire time for token is 120 minutes, you can change it in `config/sanctum.php` file.

Auth API ENDPOINTS:
```
Login:  POST: 127.0.0.1:8275/api/login. | request-body: { "email": , "password": }
Logout: POST: 127.0.0.1:8275/api/logout (auth required)
```

### Learning Laravel
Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.
