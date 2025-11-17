## About Laravel Tutorial Task App

 ***compatible with Apple (Mac Silicon | Intel Silicon)***

 PHP 8.4 | Laravel 11.x | MySQL 8.0 | Docker Compose 3.8 
 
 Composer 2.8 | Node 22.21.1 | NPM 10.9.4

#### Clone Repository
````
git clone https://github.com/aungkokoye/rti.git
cd rti
cp src/.env.example src/.env
````
#### how to start docker
````
cd docker
docker compose up --build -d
````
#### how to stop docker
````
cd docker
docker compose down
````
#### Inside app web container
````
docker exec -it laravel_app bash
````
#### Set Up

***make sure run following commands inside the `laravel_app` container***
````
- cd /var/www/html
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

***make sure run following commands inside the `laravel_app` container***
````
- cd /var/www/html
- composer require --dev barryvdh/laravel-ide-helper
- php artisan ide-helper:generate
- php artisan ide-helper:models --nowrite
````
#### Schickling MailCatcher Documentation:
For testing email functionality, we are using the Schickling MailCatcher.
It is accessible at `http://localhost:2080`.
This allows you to view emails sent by the application without needing a real email server.
MailCatcher is configured by setting the environment variable in your `.env` file:
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

- For Authentication, Laravel Sanctum is used.

- Current default expire time for token is 120 minutes, you can change it in `config/sanctum.php` file.

- For Authorization, Laravel Policy is used.

- For Rate Limiting, Laravel Throttle is used, current default is 30 requests per minute, can change it in `.env` file.

AUTH api endpoints:
```
Login:  POST: 127.0.0.1:8275/api/login. 
            request-body: 
                  { "email": <user-eamil>, 
                    "password": "password" 
                  }
            Amind user: 
                email: admin@rti.com
                password: password
            Normal user:
                email: user@rti.com
                password: password
    
Logout: POST: 127.0.0.1:8275/api/logout (auth required)

Me:     GET:  127.0.0.1:8275/api/me (auth required)
```

TASKS api endpoints:

-  Show All (GET) (auth required) : admin can get all tasks, normal user can get only his/her tasks,
                                     the result includes soft-deleted tasks.
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
- pagination-type: page or cursor (default:page)
````

-  Show Details (GET) (auth required) : user can get only his/her task
````
url: 127.0.0.1:8275/api/tasks/{task_id}?include=tags,user

- include: get related models (tags, user)
````

-  Create (POST) (auth required)
````
url: 127.0.0.1:8275/api/tasks?include=tags,user

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
- Update (POST/PATCH) (auth required) admin can update all tasks, normal user can update only his/her tasks
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
- Delete (DELETE) (auth required) admin can soft-delete all tasks, normal user can soft-delete only his/her tasks
````
url: 127.0.0.1:8275/api/tasks/{task_id}
- status: 204 No Content
````
- Restore (PATCH) (auth required) admin can restore all tasks, normal user restore only his/her tasks
````
url: 127.0.0.1:8275/api/tasks/{task_id}/restore?include=tags,user
- include: get related models (tags, user)
````
- Toggle (PATCH) (auth required) <cycle status: pending → in_progress → completed → pending>

  admin can toggle all tasks, normal user toggle only his/her tasks
````
url: 127.0.0.1:8275/api/tasks/{task_id}/toogle-status?include=tags,user
- include: get related models (tags, user)
````
TAG api endpoints:

-  Show All (GET) (auth required)
```` 
url: 127.0.0.1:8275/api/tags
````
-  Create (POST) (auth required) admin only
````
url: 127.0.0.1:8275/api/tags
- json-body:
        {
            "name"         : "test name",
            "color"        : "red",
        }
````
- Update (POST/PATCH) (auth required) admin only
````
url: 127.0.0.1:8275/api/tags/{task_id}
- json-body:
        {
            "name"         : "test name",
            "color"        : "red",
        }
````
- Delete (DELETE) (auth required) admin only
````
url:  127.0.0.1:8275/api/tags/{task_id}
- status: 204 No Content
````

#### Task Notification System

The application sends email notifications to users when their assigned tasks are modified by an admin.

How it works:

- Service Layer (`TaskService::statusChangeNotification`)
   - When an admin deletes or restores a task, the service checks if the current user is different from the assigned user
   - If different, it sends a `TaskActionNotification` to the assigned user via email
   - This ensures task owners are notified of important changes made by administrators

- Notification Class (`TaskActionNotification`)
   - Located at `app/Modules/Task/Notifications/TaskActionNotification.php`
   - Uses Laravel's `Queueable` trait for potential async delivery
   - Sends email via the `mail` channel
   - Includes task title and action performed (deleted/restored)
   - Personalized greeting with user's name

- Configuration:
  - Email service is configured via MailCatcher for development (port 2080)
  - Configure SMTP settings in `.env` for production
  - Notifications only sent when admin modifies another user's task (not their own)

- Testing Emails:
  - Access MailCatcher at `http://localhost:2080`
  - All emails sent by the application are captured here
  - No real email server required for development

#### Audit Log System

The application implements an asynchronous audit logging system using RabbitMQ message queues.

How it works:

- Service Layer (`TaskService::saveToAuditLog`)
   - When a task is created, the service dispatches an `AuditLogJob` to the RabbitMQ queue
   - Job is sent to the configured queue connection (`rabbitmq`) and queue name (`notification-queue`)
   - This keeps the API response fast by offloading database writes to background workers

- Queue Job (`AuditLogJob`)
   - Implements `ShouldQueue` interface for async processing
   - Receives: user_id, model class name, model id, JSON data, operation type
   - Creates an `AuditLog` record in the database when processed

- Database Storage (`audit_logs` table)

- Configuration
   - Queue connection: Defined in `config/queue.php` under `rabbitmq`
   - Queue name: Set via `RABBITMQ_NOTIFICATION_QUEUE` in `.env` (default: `notification`)
   - Supervisor manages the queue workers (see `supervisord.conf`)

- Queue Worker Management:

  ***make sure run following commands inside the `laravel_app` container***
```
# Start worker via Supervisor
supervisorctl start queue_worker

# View worker status
supervisorctl status queue_worker

# Process jobs manually (for testing)
php artisan queue:work rabbitmq --queue=notification
```
#### Testing:

How to run the tests (unit and feature tests) inside the `laravel_app` container:

***make sure run following commands inside the `laravel_app` container***
````
- cd /var/www/html
- php artisan db:create-test-database  // create test database if not exists
- php artisan test  
- XDEBUG_MODE=coverage php artisan test --coverage    // with code coverage
````

### Learning Laravel
Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.
