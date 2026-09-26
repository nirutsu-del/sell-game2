<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('user.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->only('name'), [
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'กรุณากรอกชื่อที่ใช้ในร้าน',
            'name.string' => 'ชื่อที่ใช้ในร้านต้องเป็นข้อความ',
            'name.max' => 'ชื่อที่ใช้ในร้านต้องไม่เกิน 100 ตัวอักษร',
        ]);

        if ($validator->fails()) {
            return redirect()->route('user.profile.edit')->withErrors($validator)
                ->withInput(['name' => is_string($request->input('name')) ? $request->input('name') : '']);
        }

        $request->user()->update($validator->validated());

        return redirect()->route('user.profile.edit')->with('success', 'บันทึกข้อมูลส่วนตัวแล้ว');
    }
}
