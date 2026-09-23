<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);
        $search = trim($filters['q'] ?? '');
        $members = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                    if (ctype_digit($search)) {
                        $query->orWhere('id', $search);
                    }
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.members.index', compact('members', 'filters'));
    }

    public function create()
    {
        return view('admin.members.form', ['member' => new User(['role' => 'user', 'status' => 'active'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(new User) + [
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ], [], $this->attributes());
        $data['status'] = $data['status'] ?? 'active';
        User::create($data);

        return redirect()->route('admin.members.index')->with('success', 'เพิ่มสมาชิกเรียบร้อยแล้ว');
    }

    public function edit(User $member)
    {
        return view('admin.members.form', compact('member'));
    }

    public function update(Request $request, User $member)
    {
        $data = $request->validate($this->rules($member), [], $this->attributes());
        if ($member->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'ไม่สามารถลดสิทธิ์ผู้ดูแลของบัญชีที่กำลังใช้งานได้'])->withInput();
        }
        $targetStatus = $data['status'] ?? $member->status ?? 'active';
        if ($member->is($request->user()) && $targetStatus === 'suspended') {
            return back()->withErrors(['status' => 'ไม่สามารถระงับการใช้งานบัญชีที่กำลังเข้าสู่ระบบอยู่ได้'])->withInput();
        }
        if ($member->email !== $data['email']) {
            $member->email_verified_at = null;
        }
        $data['status'] = $targetStatus;
        $member->fill($data)->save();

        return redirect()->route('admin.members.index')->with('success', 'บันทึกข้อมูลสมาชิกเรียบร้อยแล้ว');
    }

    private function rules(User $member): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member)],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ];
    }

    private function attributes(): array
    {
        return ['name' => 'ชื่อสมาชิก', 'email' => 'อีเมล', 'role' => 'สิทธิ์', 'status' => 'สถานะบัญชี', 'password' => 'รหัสผ่าน'];
    }
}
