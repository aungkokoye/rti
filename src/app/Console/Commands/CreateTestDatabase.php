<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;

class CreateTestDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create-test-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test database for running PHPUnit tests';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $databaseName = 'test_task';
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $charset = config('database.connections.mysql.charset', 'utf8mb4');
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        $this->info("Creating test database: {$databaseName}");

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}");

            $this->info("Test database '{$databaseName}' created successfully!");
            $this->newLine();
            $this->info("Don't forget to update your .env.testing file:");
            $this->line("DB_DATABASE={$databaseName}");

            return Command::SUCCESS;
        } catch (PDOException $e) {
            $this->error("Failed to create database: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
