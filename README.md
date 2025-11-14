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

AUTH api endpoints:
```
Login:  POST: 127.0.0.1:8275/api/login. | request-body: { "email": , "password": }
Logout: POST: 127.0.0.1:8275/api/logout (auth required)
```

TASKS api endpoints:

-  Show All (GET) (auth required) : admin can get all tasks, normal user can get only his/her tasks
```` 
url: 127.0.0.1:8275/api/tasks?search=&full-search=&status=&priority=&assigned-to=&tags=&due-date-from=
        &due-date-to=&sort=title&sort-type=desc&per-page=20&page=3&pagination-type=cursor
        
- search: search by title/description by key-words
- full-search: search by title/description by full-text
- status: filter by status (pending, in-progress, completed)
- priority: filter by priority (low, medium, high)
- assigned-to: filter by assigned user id [multiple ids separated by comma]
- tags: filter by tags [multiple tags separated by comma]
- due-date-from: filter by due date from (YYYY-MM-DD)
- due-date-to: filter by due date to (YYYY-MM-DD)
- sort: sort by field (title, due_date, priority, status, created_at)
- sort-type: asc or desc
- per-page: number of items per page
- page: page number
- pagination-type: page or cursor
````

-  Show Details (GET) (auth required) : user can get only his/her task
````
url: 127.0.0.1:8275/api/tasks/{task_id}?include=tags,user

- include: get related models (tags, user)
````

-  Create (POST) (auth required)
````
url: 127.0.0.1:8275/api/tasks/{task_id}?include=tags,user

- json-body:
        {
            "title"         : "test title",
            "description"   : "test description",
            "status"        : "pending",
            "priority"      : "low",
            "metadata"      :   {
                                    "location": "Natmouth",
                                    "link"    : "http://mcclure.com/similique-libero-magni-inventore",
                                    "uuid"    : "7da518b4-ea78-307c-bbb0-d1f293deeaf9"
                                },
            "assigned_to"   : 1,
            "due_date"      : "2026-11-21",
            "tags"          : [1,2,3]
        }
- include: get related models (tags, user)
````

### Learning Laravel
Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.
