<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\News;
use App\Models\StaticPage;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:120',
        ]);

        $term = trim($data['q']);
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        $user = $request->user('sanctum') ?: $request->user();
        $role = optional($user?->loadMissing('role')->role)->name;

        $pages = StaticPage::query()
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like)
                    ->orWhere('meta_description', 'like', $like);
            })
            ->limit(8)
            ->get()
            ->map(fn ($page) => [
                'type' => 'page',
                'title' => $page->title,
                'excerpt' => $this->excerpt($page->content),
                'url' => '/pages/' . $page->slug,
            ]);

        $news = News::query()
            ->where('is_published', true)
            ->visibleTo($role, $user)
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('content', 'like', $like);
            })
            ->latest('published_at')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'type' => 'news',
                'title' => $item->title,
                'excerpt' => $this->excerpt($item->content),
                'url' => '/news',
            ]);

        $events = Event::query()
            ->visibleTo($role, $user)
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('location', 'like', $like);
            })
            ->latest('start_time')
            ->limit(8)
            ->get()
            ->map(fn ($event) => [
                'type' => 'event',
                'title' => $event->title,
                'excerpt' => $this->excerpt($event->description ?: $event->location),
                'url' => '/events',
            ]);

        return response()->json([
            'query' => $term,
            'results' => $pages->concat($news)->concat($events)->values(),
        ]);
    }

    private function excerpt(?string $value): string
    {
        return str($value ?? '')
            ->stripTags()
            ->squish()
            ->limit(180)
            ->toString();
    }
}
