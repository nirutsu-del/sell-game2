<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index()
    {
        return view('admin.news.index', ['items' => News::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('admin.news.form', ['news' => new News()]);
    }

    public function store(Request $request)
    {
        News::create($this->validated($request) + ['slug' => (string) Str::uuid()]);
        return redirect()->route('admin.news.index')->with('success', 'เพิ่มข่าวสารแล้ว');
    }

    public function edit(News $news)
    {
        return view('admin.news.form', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $news->update($this->validated($request, $news));
        return redirect()->route('admin.news.index')->with('success', 'บันทึกข่าวสารแล้ว');
    }

    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'ลบข่าวสารแล้ว');
    }

    private function validated(Request $request, ?News $news = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:50000'],
            'is_published' => ['required', 'boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? ($news?->published_at ?? now()) : $news?->published_at;
        return $data;
    }
}
