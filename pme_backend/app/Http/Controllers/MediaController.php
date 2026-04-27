<?php
namespace App\Http\Controllers;
use App\Models\Media;
use Illuminate\Http\Request;
class MediaController extends Controller {
    public function index() { return response()->json(Media::with('uploader')->latest()->get()); }
    public function store(Request $request) {
        $request->validate(['file' => 'required|image|max:2048']);
        $path = $request->file('file')->store('media', 'public');
        $media = Media::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_url' => asset('storage/'.$path),
            'file_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
        return response()->json($media, 201);
    }
    public function destroy($id) { Media::findOrFail($id)->delete(); return response()->json(['message'=>'Deleted']); }
}