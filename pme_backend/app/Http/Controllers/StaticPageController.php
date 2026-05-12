<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;
use Illuminate\Http\Request;

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
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $page->fill($data);
        $page->save();

        return response()->json($page);
    }
}
