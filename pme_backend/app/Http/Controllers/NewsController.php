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
            'is_published' => 'nullable|boolean',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max
        ]);

        $data['author_id'] = auth()->id();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        unset($data['image']); // don't pass raw file to model
        $news = News::create($data);

        return response()->json($news->load('author'), 201);
    }

    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $data = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'content'      => 'sometimes|required|string',
            'is_published' => 'nullable|boolean',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($news->image_path) {
                Storage::disk('public')->delete($news->image_path);
            }
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        unset($data['image']);
        $news->update($data);

        return response()->json($news->load('author'));
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