<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AuditLogJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $className,
        private readonly int $classId,
        private readonly string $data,
        private readonly string $operationType,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AuditLog::create([
                'user_id'       => $this->userId,
                'class_name'    => $this->className,
                'class_id'      => $this->classId,
                'change_data'   => $this->data,
                'operation_type'=> $this->operationType,
            ]
        );
    }
}
