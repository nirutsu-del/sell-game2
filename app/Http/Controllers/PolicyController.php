<?php

namespace App\Http\Controllers;

use App\Models\StorePolicy;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function show(string $slug)
    {
        abort_unless(isset(StorePolicy::PAGES[$slug]), 404);
        return view('policies.show', [
            'title' => StorePolicy::PAGES[$slug],
            'policy' => StorePolicy::where('slug', $slug)->where('is_published', true)->first(),
        ]);
    }

    public function edit()
    {
        return view('admin.policies.index', ['policies' => StorePolicy::all()->keyBy('slug')]);
    }

    public function update(Request $request, string $slug)
    {
        abort_unless(isset(StorePolicy::PAGES[$slug]), 404);
        $data = $request->validate([
            'content' => ['nullable', 'required_if:is_published,1', 'string', 'max:50000'],
            'is_published' => ['required', 'boolean'],
        ]);
        StorePolicy::updateOrCreate(['slug' => $slug], $data);
        return redirect()->route('admin.policies.edit')->with('success', 'บันทึก'.StorePolicy::PAGES[$slug].'แล้ว');
    }
}
