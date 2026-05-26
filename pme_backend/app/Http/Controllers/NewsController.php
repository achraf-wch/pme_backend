<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class NewsController extends Controller
{
    use ScopesByPartyBranch;

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

        $news = News::with(['author', 'partyBranch'])
            ->when($data['q'] ?? null, function ($query, $q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $query->where(fn ($sub) => $sub->where('title', 'like', $like)->orWhere('content', 'like', $like));
            })
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($data['topic'] ?? null, fn ($query, $topic) => $query->where('topic', $topic))
            ->when($data['region'] ?? null, fn ($query, $region) => $query->where('region', $region))
            ->when(($data['archived'] ?? false), fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->latest()
            ->when($request->user(), function ($query, $user) {
                return $this->applyManagedBranchScope($query, $user);
            })
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

        return response()->json($this->visibleFeedForRole($role, $user)->get());
    }

    public function mine(Request $request)
    {
        $role = optional($request->user()->loadMissing('role')->role)->name;

        return response()->json($this->visibleFeedForRole($role, $request->user())->get());
    }

    private function visibleFeedForRole(?string $role, $user = null)
    {
        $news = News::with(['author', 'partyBranch'])
            ->where('is_published', true)
            ->whereNull('archived_at')
            ->visibleTo($role, $user)
            ->latest();

        return $news;
    }

    private function booleanInput(Request $request, string $key, bool $default = false): bool
    {
        if (!$request->has($key)) {
            return $default;
        }

        $value = $request->input($key);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    public function show(Request $request, News $news)
    {
        $user = $request->user('sanctum') ?: $request->user();
        $role = optional($user?->loadMissing('role')->role)->name;

        return $this->showForRole($news, $role, $user);
    }

    public function showMine(Request $request, News $news)
    {
        $role = optional($request->user()->loadMissing('role')->role)->name;

        return $this->showForRole($news, $role, $request->user());
    }

    private function showForRole(News $news, ?string $role, $user = null)
    {
        $visible = News::query()
            ->whereKey($news->id)
            ->where('is_published', true)
            ->whereNull('archived_at')
            ->visibleTo($role, $user)
            ->exists();

        if (!$visible) {
            abort(404);
        }

        return response()->json($news->load(['author', 'partyBranch']));
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
            'auto_share_social' => 'nullable',
            'social_channels' => 'nullable|array',
            'social_channels.*' => 'string|in:facebook,x,instagram,linkedin,whatsapp',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
        ]);

        $data['author_id'] = auth()->id();
        $this->ensureAudienceAllowedForWrite($request->user(), $data['audience']);
        $this->ensureCanManageBranch($request->user(), $data['party_branch_id'] ?? null);
        $data['party_branch_id'] = $this->branchIdForWrite($request->user(), $data['party_branch_id'] ?? null);
        $data['is_published'] = $this->booleanInput($request, 'is_published', true);
        $data['auto_share_social'] = $this->booleanInput($request, 'auto_share_social', false);
        $data['social_channels'] = $data['auto_share_social'] ? ($data['social_channels'] ?? []) : [];

        if ($data['is_published']) {
            $data['published_at'] = now();
        }
        $data['type'] = $data['type'] ?? 'news';

        $storedPaths = [];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
            $storedPaths[] = $data['image_path'];
        }
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('news/attachments', 'public');
            $storedPaths[] = $data['attachment_path'];
        }

        unset($data['image'], $data['attachment']);

        try {
            $news = DB::transaction(function () use ($data) {
                $news = News::create($data);

                if ($news->is_published) {
                    $this->notifications->notifyAudience($news->audience ?? ['public'], [
                        'category' => 'content',
                        'title' => 'Nouvelle actualité',
                        'body' => $news->title,
                        'action_url' => "/news/{$news->id}",
                        'action_label' => 'Lire',
                        'source_type' => 'news',
                        'source_id' => $news->id,
                    ], auth()->id(), $news->party_branch_id);
                }

                return $news;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return response()->json($news->load(['author', 'partyBranch']), 201);
    }

    /**
     * Admin: update a news article.
     */
    public function update(Request $request, $id)
    {
        $newsItem = News::findOrFail($id);
        $this->ensureCanAccessNews($request, $newsItem);

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
            'auto_share_social' => 'nullable',
            'social_channels' => 'nullable|array',
            'social_channels.*' => 'string|in:facebook,x,instagram,linkedin,whatsapp',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'attachment'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
        ]);

        if (array_key_exists('is_published', $data)) {
            $data['is_published'] = $this->booleanInput($request, 'is_published');
            if ($data['is_published'] && !$newsItem->published_at) {
                $data['published_at'] = now();
            }
        }
        if ($request->has('auto_share_social')) {
            $data['auto_share_social'] = $this->booleanInput($request, 'auto_share_social');
            $data['social_channels'] = $data['auto_share_social'] ? ($data['social_channels'] ?? []) : [];
        }
        if (array_key_exists('audience', $data)) {
            $this->ensureAudienceAllowedForWrite($request->user(), $data['audience']);
        }
        if (array_key_exists('party_branch_id', $data)) {
            $this->ensureCanManageBranch($request->user(), $data['party_branch_id']);
        }
        if (array_key_exists('party_branch_id', $data)) {
            $data['party_branch_id'] = $this->branchIdForWrite($request->user(), $data['party_branch_id']);
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

        return response()->json($newsItem->load(['author', 'partyBranch']));
    }

    /**
     * Admin: delete a news article.
     */
    public function destroy($id)
    {
        $news = News::findOrFail($id);
        $this->ensureCanAccessNews(request(), $news);

        if ($news->image_path) {
            Storage::disk('public')->delete($news->image_path);
        }
        if ($news->attachment_path) {
            Storage::disk('public')->delete($news->attachment_path);
        }

        $news->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function ensureCanAccessNews(Request $request, News $news): void
    {
        $actor = $request->user();
        $branchIds = $this->managedBranchIdsVisibleTo($actor);

        if ($branchIds !== null && !in_array((int) $news->party_branch_id, $branchIds, true)) {
            abort(403, 'You are not allowed to manage this news item.');
        }
    }
}
