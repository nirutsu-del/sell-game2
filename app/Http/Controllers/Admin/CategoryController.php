<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['categories' => Category::withCount('gameAccounts')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category(), 'parents'=>Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'เพิ่มเกมสำหรับรายการสินค้าแล้ว');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', ['category'=>$category,'parents'=>Category::where('id','!=',$category->id)->orderBy('name')->get()]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'บันทึกการแก้ไขเกมแล้ว');
    }

    public function destroy(Category $category)
    {
        abort_if($category->gameAccounts()->exists() || $category->products()->exists() || $category->children()->exists(), 422, 'ไม่สามารถลบหมวดหมู่ที่มีสินค้า ไอดี หรือหมวดหมู่ย่อยได้');
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'ลบเกมแล้ว');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable','exists:categories,id'],
            'sort_order' => ['nullable','integer','min:0','max:10000'],
            'is_featured' => ['nullable','boolean'],
            'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:categories,slug,' . ($category?->id ?? 'NULL')],
        ]);

        $data['slug'] = $data['slug'] ?: Str::lower(Str::random(12));
        $cursor = !empty($data['parent_id']) ? Category::find($data['parent_id']) : null;
        $visited = [];
        while ($cursor) {
            if ($cursor->id === $category?->id || in_array($cursor->id,$visited)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['parent_id'=>'หมวดหมู่หลักต้องไม่เป็นตัวเองหรือหมวดหมู่ลูก']);
            }
            $visited[] = $cursor->id; $cursor = $cursor->parent;
        }
        $data['is_featured'] = $request->boolean('is_featured');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('categories','public');
        else unset($data['image']);

        return $data;
    }
}
