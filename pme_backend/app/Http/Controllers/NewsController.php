<?php
namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index()
    {
        return response()->json(News::with('author')->latest()->get());
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'title'        => 'required|string|max:255',
        'content'      => 'required|string',
        'is_published' => 'nullable',      // ← accept any value
        'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
    ]);

    $data['author_id']    = auth()->id();
    $data['is_published'] = filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN);

    if ($request->hasFile('image')) {
        $data['image_path'] = $request->file('image')->store('news', 'public');
    }

    unset($data['image']);
    $news = News::create($data);
    return response()->json($news->load('author'), 201);
}

public function update(Request $request, $news)
{
    $newsItem = News::findOrFail(is_object($news) ? $news->id : $news);

    $data = $request->validate([
        'title'        => 'sometimes|required|string|max:255',
        'content'      => 'sometimes|required|string',
        'is_published' => 'nullable',
        'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
    ]);

    if (isset($data['is_published'])) {
        $data['is_published'] = filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN);
    }

    if ($request->hasFile('image')) {
        if ($newsItem->image_path) {
            \Storage::disk('public')->delete($newsItem->image_path);
        }
        $data['image_path'] = $request->file('image')->store('news', 'public');
    }

    unset($data['image']);
    $newsItem->update($data);
    return response()->json($newsItem->load('author'));
}

    

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