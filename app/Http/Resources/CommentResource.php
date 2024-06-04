<?php

namespace App\Http\Resources;

use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CommentResource extends JsonResource
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
            'body' => $this->body,
            'post_id' => $this->post_id,
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'reactions_count' => $this->likes_count,
            'current_user_has_reaction' => $this->reactions->count() > 0,
            'parent_id' => $this->parent_id,
            'comments_count' => $this->comments_count,
            'comments' => CommentResource::collection($this->comments),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar_url' => Storage::url($this->user->avatar_path),
            ],
        ];
    }
}
