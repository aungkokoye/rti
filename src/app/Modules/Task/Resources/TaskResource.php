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
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $deleted_at
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
            'due_date'      => $this->due_date?->toDateString(),
            'assigned_to'   => UserResource::make($this->whenLoaded('user')),
            'tags'          => TagResource::collection($this->whenLoaded('tags')),
            'deleted_at'    => $this->deleted_at?->toDateTimeString(),
            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'    => $this->updated_at->toDateTimeString(),
        ];
    }


}
