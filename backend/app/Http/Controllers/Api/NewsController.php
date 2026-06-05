<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsPostRequest;
use App\Http\Requests\UpdateNewsPostRequest;
use App\Http\Resources\NewsPostResource;
use App\Models\NewsPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NewsPost::class);

        $search = $request->query('search');

        $newsPosts = NewsPost::with('author')
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return NewsPostResource::collection($newsPosts)
            ->response();
    }

    public function show(NewsPost $newsPost): JsonResponse
    {
        $this->authorize('view', $newsPost);

        $newsPost->load('author');

        return NewsPostResource::make($newsPost)
            ->response();
    }

    public function store(StoreNewsPostRequest $request): JsonResponse
    {
        $this->authorize('create', NewsPost::class);

        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        $newsPost = NewsPost::create($data);
        $newsPost->load('author');

        return NewsPostResource::make($newsPost)
            ->additional(['message' => 'News post created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateNewsPostRequest $request, NewsPost $newsPost): JsonResponse
    {
        $this->authorize('update', $newsPost);

        $newsPost->update($request->validated());
        $newsPost->load('author');

        return NewsPostResource::make($newsPost)
            ->additional(['message' => 'News post updated successfully'])
            ->response();
    }

    public function destroy(NewsPost $newsPost): JsonResponse
    {
        $this->authorize('delete', $newsPost);

        $newsPost->delete();

        return response()->json([
            'message' => 'News post deleted successfully',
        ]);
    }
}
