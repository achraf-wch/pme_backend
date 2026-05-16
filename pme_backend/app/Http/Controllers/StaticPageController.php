<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaticPageController extends Controller
{
    public function index()
    {
        return response()->json(StaticPage::orderBy('title')->get());
    }

    public function show(string $slug)
    {
        return response()->json(StaticPage::where('slug', $slug)->firstOrFail());
    }

    public function update(Request $request, string $slug)
    {
        $page = StaticPage::firstOrNew(['slug' => $slug]);

        $data = $request->validate([
            'title' => ($page->exists ? 'sometimes' : 'required') . '|required|string|max:255',
            'content' => ($page->exists ? 'sometimes' : 'required') . '|required|string',
            'images' => 'nullable|array',
            'images.*.path' => 'required_with:images|string|max:1000',
            'images.*.caption' => 'nullable|string|max:255',
            'images.*.layout' => 'nullable|string|in:single,two,list',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|max:5120',
            'new_image_captions' => 'nullable|array',
            'new_image_captions.*' => 'nullable|string|max:255',
            'new_image_layouts' => 'nullable|array',
            'new_image_layouts.*' => 'nullable|string|in:single,two,list',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $images = collect($data['images'] ?? [])
            ->map(fn ($image) => [
                'path' => $image['path'],
                'caption' => $image['caption'] ?? '',
                'layout' => $image['layout'] ?? 'list',
            ])
            ->values()
            ->all();

        foreach ($request->file('new_images', []) as $index => $image) {
            $images[] = [
                'path' => $image->store('static-pages', 'public'),
                'caption' => $request->input("new_image_captions.{$index}", ''),
                'layout' => $request->input("new_image_layouts.{$index}", 'list') ?: 'list',
            ];
        }

        $oldPaths = collect($page->images ?? [])->pluck('path')->filter()->all();
        $nextPaths = collect($images)->pluck('path')->filter()->all();
        foreach (array_diff($oldPaths, $nextPaths) as $removedPath) {
            Storage::disk('public')->delete($removedPath);
        }

        $data['images'] = $images;
        unset($data['new_images'], $data['new_image_captions'], $data['new_image_layouts']);

        $page->fill($data);
        $page->save();

        return response()->json($page);
    }
}
