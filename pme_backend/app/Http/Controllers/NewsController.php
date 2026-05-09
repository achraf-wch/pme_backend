<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Admin: all news regardless of audience.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:120',
            'type' => 'nullable|string|max:40',
            'topic' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'archived' => 'nullable|boolean',
        ]);

        $news = News::with('author')
            ->when($data['q'] ?? null, function ($query, $q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $query->where(fn ($sub) => $sub->where('title', 'like', $like)->orWhere('content', 'like', $like));
            })
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($data['topic'] ?? null, fn ($query, $topic) => $query->where('topic', $topic))
            ->when($data['region'] ?? null, fn ($query, $region) => $query->where('region', $region))
            ->when(($data['archived'] ?? false), fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->latest()
            ->get();

        return response()->json($news);
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
        $user = $request->user('sanctum') ?: $request->user();
        $role = optional($user?->loadMissing('role')->role)->name;

        $news = News::with('author')
            ->where('is_published', true)
            ->whereNull('archived_at')
            ->where('published_at', '<=', now())
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
            'type'         => 'nullable|string|in:news,communique,article',
            'topic'        => 'nullable|string|max:255',
            'region'       => 'nullable|string|max:255',
            'content'      => 'required|string',
            'is_published' => 'nullable',
            'published_at' => 'nullable|date',
            'audience'     => 'required|array|min:1',
            'audience.*'   => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
        ]);

        $data['author_id']    = auth()->id();
        $data['is_published'] = filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN);

        if ($data['is_published']) {
            $data['published_at'] = $data['published_at'] ?? now();
        }
        $data['type'] = $data['type'] ?? 'news';

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('news/attachments', 'public');
        }

        unset($data['image'], $data['attachment']);

        $news = News::create($data);

        if ($news->is_published) {
            $this->notifications->notifyAudience($news->audience ?? ['public'], [
                'category' => 'content',
                'title' => 'Nouvelle actualité',
                'body' => $news->title,
                'action_url' => '/news',
                'action_label' => 'Lire',
                'source_type' => 'news',
                'source_id' => $news->id,
            ], auth()->id());
        }

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
            'type'         => 'nullable|string|in:news,communique,article',
            'topic'        => 'nullable|string|max:255',
            'region'       => 'nullable|string|max:255',
            'content'      => 'sometimes|required|string',
            'is_published' => 'nullable',
            'published_at' => 'nullable|date',
            'archived_at'  => 'nullable|date',
            'audience'     => 'sometimes|required|array|min:1',
            'audience.*'   => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
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
        if ($request->hasFile('attachment')) {
            if ($newsItem->attachment_path) {
                Storage::disk('public')->delete($newsItem->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('news/attachments', 'public');
        }

        unset($data['image'], $data['attachment']);
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
        if ($news->attachment_path) {
            Storage::disk('public')->delete($news->attachment_path);
        }

        $news->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
