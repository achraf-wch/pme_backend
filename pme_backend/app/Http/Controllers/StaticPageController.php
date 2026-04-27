<?php
namespace App\Http\Controllers;
use App\Models\StaticPage;
use Illuminate\Http\Request;
class StaticPageController extends Controller {
    public function index() { return response()->json(StaticPage::all()); }
    public function update(Request $request, $slug) {
        $page = StaticPage::where('slug',$slug)->firstOrFail();
        $page->update($request->only(['title','content','meta_title','meta_description']));
        return response()->json($page);
    }
}