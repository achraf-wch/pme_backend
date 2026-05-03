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
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $page = StaticPage::updateOrCreate(['slug' => $slug], $data);

        return response()->json($page);
    }
}
