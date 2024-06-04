<?php

namespace App\Http\Controllers;

use App\Http\Enums\ReactionEnum;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\Reaction;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PostController extends Controller
{
    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $allFilePaths = [];
        try {
            DB::beginTransaction();

            $post = Post::query()->create($data);

            /** @var UploadedFile[] $files */
            $files = $data['attachments'] ?? [];

            foreach ($files as $file) {
                $path = $file->storeAs("attachments/$post->id", Str::random(32) . '.jpg', 'public');
                $allFilePaths[] = $path;

                PostAttachment::query()->create([
                    'post_id' => $post->id,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'created_by' => $request->user(),
                ]);
            }

            Db::commit();
        } catch (Throwable) {
            Storage::disk('public')->delete($allFilePaths);
            Db::rollBack();
        }

        return back();
    }

    public function update(Post $post, UpdatePostRequest $request): RedirectResponse
    {
        $allFilePaths = [];
        $data = $request->validated();

        DB::beginTransaction();
        try {

            $post->update($data);

            $deletedFileIds = $data['deleted_file_ids'] ?? [];

            if ($deletedFileIds) {

                $attachments = PostAttachment::query()
                    ->where('post_id', $post->id)
                    ->whereIn('id', $deletedFileIds)
                    ->get();

                foreach ($attachments as $attachment) {
                    $attachment->delete();
                }
            }

            /** @var UploadedFile[] $files */
            $files = $data['attachments'];

            foreach ($files as $file) {
                $path = $file->storeAs("attachments/$post->id", Str::random(32) . '.jpg', 'public');
                $allFilePaths[] = $path;

                PostAttachment::query()->create([
                    'post_id' => $post->id,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'created_by' => $request->user(),
                ]);
            }

            Db::commit();
        } catch (Throwable) {
            Storage::disk('public')->delete($allFilePaths);
            Db::rollBack();
        }

        return back();
    }

    public function postReaction(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate([
            'reaction' => Rule::enum(ReactionEnum::class)
        ]);

        $reaction = Reaction::query()
            ->where('user_id', auth()->id())
            ->where('reactionable_id', $post->id)
            ->where('reactionable_type', Post::class)
            ->first();

        if ($reaction) {
            $hasReaction = false;
            $reaction->delete();
        } else {
            $hasReaction = true;

            Reaction::query()->create([
                'reactionable_id' => $post->id,
                'reactionable_type' => Post::class,
                'user_id' => auth()->id(),
                'type' => $data['reaction']
            ]);
        }

        $reactionCount = Reaction::query()
            ->where('reactionable_id', $post->id)
            ->where('reactionable_type', Post::class)
            ->count();

        return response()->json([
            'reactions_count' => $reactionCount,
            'current_user_has_reaction' => $hasReaction,
        ]);
    }

    public function download(PostAttachment $attachment): BinaryFileResponse
    {
        // Both approaches are working

        return response()->download(Storage::disk('public')->path($attachment->path), $attachment->name);
        //Storage::download("app/public/$attachment->path", $attachment->name);
    }

    public function createComment(Request $request, Post $post): CommentResource
    {

        $data = $request->validate([
            'comment' => 'required',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'body' => nl2br($data['comment']),
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return new CommentResource($comment, 201);
    }

    public function updateComment(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        $data = $request->validated();

        $comment->update($data);

        return new CommentResource($comment);
    }

    public function deleteComment(Comment $comment): Response|Application|ResponseFactory
    {
        if ($comment->user_id !== Auth::id()) {
            return response("You don't have permission to delete this comment", 403);
        }

        $comment->delete();

        return response('', 204);
    }

    public function commentReaction(Request $request, Comment $comment): JsonResponse
    {
        $data = $request->validate([
            'reaction' => Rule::enum(ReactionEnum::class)
        ]);

        $reaction = Reaction::query()
            ->where('user_id', auth()->id())
            ->where('reactionable_id', $comment->id)
            ->where('reactionable_type', Comment::class)
            ->first();

        if ($reaction) {
            $hasReaction = false;
            $reaction->delete();
        } else {
            $hasReaction = true;

            Reaction::query()->create([
                'reactionable_id' => $comment->id,
                'reactionable_type' => Comment::class,
                'user_id' => auth()->id(),
                'type' => $data['reaction']
            ]);
        }

        $reactionCount = Reaction::query()
            ->where('reactionable_id', $comment->id)
            ->where('reactionable_type', Comment::class)
            ->count();

        return response()->json([
            'reactions_count' => $reactionCount,
            'current_user_has_reaction' => $hasReaction,
        ]);
    }

    public function destroy(Post $post): Response|Application|RedirectResponse|ResponseFactory
    {
        if ($post->user_id !== auth()->id()) {
            return response("You don't have permission to delete this post", 403);
        }

        $post->delete();
        return back();
    }
}
