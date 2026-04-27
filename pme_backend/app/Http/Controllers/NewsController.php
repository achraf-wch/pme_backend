<?php
namespace App\Http\Controllers;
use App\Models\News;
use Illuminate\Http\Request;
class NewsController extends Controller {
    public function index() { return response()->json(News::with('author')->latest()->get()); }
    public function store(Request $request) {
        $data = $request->validate(['title'=>'required','content'=>'required']);
        $data['author_id'] = auth()->id();
        $news = News::create($data);
        return response()->json($news, 201);
    }
    public function update(Request $request, $id) {
        $news = News::findOrFail($id);
        $news->update($request->only(['title','content','is_published']));
        return response()->json($news);
    }
    public function destroy($id) { News::findOrFail($id)->delete(); return response()->json(['message'=>'Deleted']); }
}