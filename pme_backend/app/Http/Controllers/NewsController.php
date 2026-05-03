<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    /**
     * Admin: all news regardless of audience.
     */
    public function index()
    {
        return response()->json(News::with('author')->latest()->get());
    }

    /**
     * Public / member-facing: only news the authenticated user (or guest) can see.
     *
     * GET /api/news/feed
     * Works for:
     *  - unauthenticated guests  → sees 'public' articles only
     *  - authenticated users     → sees 'public' + their role
     */
    public function feed(Request $request)
    {
        $role = optional($request->user()?->role)->name;

        $news = News::with('author')
            ->where('is_published', true)
            ->visibleTo($role)
            ->latest()
            ->get();

        return response()->json($news);
    }

    /**
     * Admin: create a news article.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'is_published' => 'nullable',
            'audience'     => 'required|array|min:1',
            'audience.*'   => 'string|in:public,visitor,sympathizer,member,admin,local_official,central_admin,super_admin',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        $data['author_id']    = auth()->id();
        $data['is_published'] = filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN);

        if ($data['is_published']) {
            $data['published_at'] = now();
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        unset($data['image']);

        $news = News::create($data);

        return response()->json($news->load('author'), 201);
    }

    /**
     * Admin: update a news article.
     */
    public function update(Request $request, $id)
    {
        $newsItem = News::findOrFail($id);

        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'content'      => 'sometimes|required|string',
            'is_published' => 'nullable',
            'audience'     => 'sometimes|required|array|min:1',
            'audience.*'   => 'string|in:public,visitor,sympathizer,member,admin,local_official,central_admin,super_admin',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        if (isset($data['is_published'])) {
            $data['is_published'] = filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN);
            if ($data['is_published'] && !$newsItem->published_at) {
                $data['published_at'] = now();
            }
        }

        if ($request->hasFile('image')) {
            if ($newsItem->image_path) {
                Storage::disk('public')->delete($newsItem->image_path);
            }
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        unset($data['image']);
        $newsItem->update($data);

        return response()->json($newsItem->load('author'));
    }

    /**
     * Admin: delete a news article.
     */
    public function destroy($id)
    {
        $news = News::findOrFail($id);

        if ($news->image_path) {
            Storage::disk('public')->delete($news->image_path);
        }

        $news->delete();

        return response()->json(['message' => 'Deleted']);
    }
}