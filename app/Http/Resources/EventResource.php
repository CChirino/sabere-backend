<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => Event::TYPE_LABELS[$this->type] ?? $this->type,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'all_day' => $this->all_day,
            'location' => $this->location,
            'color' => $this->display_color,
            'visibility' => $this->visibility,
            'visibility_label' => Event::VISIBILITY_LABELS[$this->visibility] ?? $this->visibility,
            'send_notification' => $this->send_notification,
            'status' => $this->status,
            'academic_period_id' => $this->academic_period_id,
            'academic_period' => $this->whenLoaded('academicPeriod', fn () => [
                'id' => $this->academicPeriod->id,
                'name' => $this->academicPeriod->name,
            ]),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
