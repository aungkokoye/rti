<?php
declare(strict_types=1);

namespace App\Modules\Task\Resources;


use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $title
 * @property mixed $description
 * @property mixed $status
 * @property mixed $priority
 * @property mixed $metadata
 * @property mixed $due_date
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'status'        => $this->status,
            'priority'      => $this->priority,
            'metadata'      => $this->metadata,
            'due_date'      => $this->due_date,
            'assigned_to'   => UserResource::make($this->whenLoaded('user')),
            'tags'          => TagResource::collection($this->whenLoaded('tags')),
        ];
    }


}
