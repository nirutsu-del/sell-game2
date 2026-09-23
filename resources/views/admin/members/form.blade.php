@extends('layouts.app')

@section('content')
<div class="max-w-xl">
    <a href="{{ route('admin.members.index') }}" class="text-sm text-violet-300">← กลับหน้าจัดการสมาชิก</a>
    <h1 class="mt-4 text-2xl font-bold">{{ $member->exists ? 'แก้ไขสมาชิก' : 'เพิ่มสมาชิก' }}</h1>
    @if($member->exists)<p class="mt-2 text-sm text-slate-400">สมาชิก #{{ $member->id }} · ยอด Wallet ฿{{ number_format($member->balance, 2) }}</p>@endif
    <form id="member-form" method="POST" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}" class="mt-6 space-y-4">
        @csrf
        @if($member->exists) @method('PUT') @endif
        <label class="block">ชื่อสมาชิก<input name="name" value="{{ old('name', $member->name) }}" maxlength="100" autocomplete="name" class="mt-2 w-full rounded bg-slate-800 p-3" required>@error('name')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
        <label class="block">อีเมล<input type="email" name="email" value="{{ old('email', $member->email) }}" maxlength="255" autocomplete="email" class="mt-2 w-full rounded bg-slate-800 p-3" required>@error('email')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
        @if($member->is(auth()->user()))
            <input type="hidden" name="role" value="admin">
            <p class="rounded bg-slate-800 p-3">สิทธิ์: ผู้ดูแลระบบ <span class="mt-1 block text-sm text-slate-400">บัญชีที่กำลังใช้งานไม่สามารถลดสิทธิ์ตัวเองได้</span></p>
            <input type="hidden" name="status" value="active">
            <div class="rounded bg-slate-800 p-3">
                <span class="text-sm text-slate-400">สถานะบัญชี:</span> <span class="font-medium text-emerald-400">🟢 เปิดใช้งาน</span>
                <span class="mt-1 block text-sm text-slate-400">บัญชีที่กำลังใช้งานไม่สามารถระงับการใช้งานตัวเองได้</span>
            </div>
        @else
            <label class="block">สิทธิ์<select name="role" class="mt-2 w-full rounded bg-slate-800 p-3" required><option value="user" @selected(old('role', $member->role) === 'user')>สมาชิก</option><option value="admin" @selected(old('role', $member->role) === 'admin')>ผู้ดูแลระบบ</option></select>@error('role')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
            <p class="text-sm text-slate-400">ผู้ดูแลระบบสามารถเข้าถึงและจัดการข้อมูลหลังบ้านทั้งหมด</p>
            <label class="block">สถานะบัญชี
                <select name="status" class="mt-2 w-full rounded bg-slate-800 p-3" required>
                    <option value="active" @selected(old('status', $member->status ?? 'active') === 'active')>🟢 เปิดใช้งาน</option>
                    <option value="suspended" @selected(old('status', $member->status ?? 'active') === 'suspended')>🔴 ระงับการใช้งาน</option>
                </select>
                @error('status')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror
            </label>
            <p class="text-sm text-slate-400">บัญชีที่ถูกระงับจะไม่สามารถเข้าสู่ระบบหรือทำรายการใดๆ ในร้านได้</p>
        @endif
        @unless($member->exists)
            <label class="block">รหัสผ่าน<input type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" class="mt-2 w-full rounded bg-slate-800 p-3" required><span class="mt-1 block text-sm text-slate-400">อย่างน้อย 8 ตัวอักษร</span>@error('password')<span class="mt-1 block text-sm text-red-300">{{ $message }}</span>@enderror</label>
            <label class="block">ยืนยันรหัสผ่าน<input type="password" name="password_confirmation" minlength="8" maxlength="128" autocomplete="new-password" class="mt-2 w-full rounded bg-slate-800 p-3" required></label>
        @endunless
        <div class="flex gap-3">
            @if($member->exists)
                <button type="button" id="save-member-btn" class="rounded bg-violet-600 px-5 py-3 font-semibold hover:opacity-90 transition">บันทึกการแก้ไข</button>
            @else
                <button type="submit" class="rounded bg-violet-600 px-5 py-3 font-semibold">เพิ่มสมาชิก</button>
            @endif
            <a href="{{ route('admin.members.index') }}" class="rounded border border-slate-700 px-5 py-3">ยกเลิก</a>
        </div>
    </form>

    @if($member->exists)
    {{-- Danger Zone: การจัดการบัญชีเพิ่มเติม --}}
    <div class="mt-12 rounded-2xl border border-rose-900/50 bg-rose-950/20 p-6">
        <div>
            <h2 class="text-base font-bold text-rose-300 flex items-center gap-2">
                <span>⚠️</span> การจัดการบัญชีเพิ่มเติม
            </h2>
            <p class="mt-1 text-xs text-slate-400">
                การดำเนินการด้านล่างมีผลกระทบต่อสถานะและการเข้าถึงระบบของสมาชิกโดยตรง โปรดใช้ความระมัดระวัง
            </p>
        </div>

        <div class="mt-5 flex flex-wrap items-center gap-3">
            @if($member->is(auth()->user()))
                <p class="text-xs text-slate-500 italic">บัญชีที่กำลังเข้าสู่ระบบอยู่ ไม่สามารถระงับหรือลบบัญชีของตนเองได้</p>
            @else
                {{-- ปุ่มระงับ / ปลดระงับสมาชิก --}}
                <form method="POST" action="{{ route('admin.members.toggle-status', $member) }}" id="toggle-status-form">
                    @csrf
                    @if($member->isSuspended())
                        <button type="button" data-open-dialog="confirm-toggle-status-modal" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-2.5 text-sm font-semibold text-emerald-300 hover:bg-emerald-500/25 transition">
                            <span>🟢</span> ปลดระงับ / เปิดใช้งานสมาชิก
                        </button>
                    @else
                        <button type="button" data-open-dialog="confirm-toggle-status-modal" class="inline-flex items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/15 px-4 py-2.5 text-sm font-semibold text-amber-300 hover:bg-amber-500/25 transition">
                            <span>🔒</span> ระงับสมาชิก
                        </button>
                    @endif
                </form>

                {{-- ปุ่มลบบัญชี --}}
                <button type="button" data-open-dialog="confirm-delete-member-modal" class="inline-flex items-center gap-2 rounded-xl border border-rose-500/40 bg-rose-500/15 px-4 py-2.5 text-sm font-semibold text-rose-300 hover:bg-rose-500/25 transition">
                    <span>🗑️</span> ลบบัญชี
                </button>
            @endif
        </div>
    </div>

    {{-- Modal ยืนยันก่อนบันทึก --}}
    <dialog id="confirm-save-modal" class="vault-dialog">
        <div class="vault-dialog-heading">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span>❓</span> ยืนยันการเปลี่ยนแปลงข้อมูลสมาชิก?
            </h2>
            <button type="button" data-close-dialog aria-label="ปิด">×</button>
        </div>

        <div class="mt-4 space-y-3 text-sm text-slate-300">
            <p>คุณกำลังจะบันทึกการเปลี่ยนแปลงข้อมูลของ <strong class="text-white break-all" id="summary-member-name">{{ $member->name }}</strong></p>

            <div id="role-change-warning" class="hidden rounded-xl border border-amber-500/40 bg-amber-500/10 p-3.5 text-amber-200">
                <p class="font-semibold flex items-center gap-1.5">
                    <span>⚠️</span> คำเตือนเรื่องสิทธิ์การใช้งาน:
                </p>
                <p class="mt-1 text-xs leading-relaxed" id="role-change-text"></p>
            </div>

            <div id="status-change-warning" class="hidden rounded-xl border border-rose-500/40 bg-rose-500/10 p-3.5 text-rose-200">
                <p class="font-semibold flex items-center gap-1.5">
                    <span>⚠️</span> คำเตือนสถานะบัญชี:
                </p>
                <p class="mt-1 text-xs leading-relaxed" id="status-change-text"></p>
            </div>

            <div class="rounded-lg bg-slate-800/80 p-3 text-xs text-slate-300 space-y-1.5">
                <p id="summary-email-line" class="hidden text-amber-300"></p>
                <p id="summary-role-line"></p>
                <p id="summary-status-line"></p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-close-dialog class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:bg-slate-800 transition">
                ยกเลิก
            </button>
            <button type="button" id="confirm-save-submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500 transition">
                ยืนยัน
            </button>
        </div>
    </dialog>

    @if(!$member->is(auth()->user()))
    {{-- Modal ยืนยัน ระงับ / ปลดระงับ --}}
    <dialog id="confirm-toggle-status-modal" class="vault-dialog">
        <div class="vault-dialog-heading">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <span>{{ $member->isSuspended() ? '🟢' : '🔒' }}</span>
                {{ $member->isSuspended() ? 'ยืนยันเปิดใช้งานบัญชี?' : 'ยืนยันการระงับสมาชิก?' }}
            </h2>
            <button type="button" data-close-dialog aria-label="ปิด">×</button>
        </div>
        <p class="mt-4 text-sm text-slate-300">
            @if($member->isSuspended())
                คุณต้องการปลดระงับและเปิดใช้งานบัญชีของ <strong class="text-white">{{ $member->name }}</strong> ใช่หรือไม่? สมาชิกจะสามารถกลับมาเข้าสู่ระบบได้ตามปกติ
            @else
                คุณต้องการระงับการใช้งานบัญชีของ <strong class="text-white">{{ $member->name }}</strong> ใช่หรือไม่? สมาชิกจะไม่สามารถเข้าสู่ระบบหรือทำรายการใดๆ ในร้านได้จนกว่าจะเปิดใช้งานอีกครั้ง
            @endif
        </p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-close-dialog class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:bg-slate-800 transition">
                ยกเลิก
            </button>
            <button type="button" onclick="document.getElementById('toggle-status-form').submit();" class="rounded-lg {{ $member->isSuspended() ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-amber-600 hover:bg-amber-500' }} px-5 py-2.5 text-sm font-semibold text-white transition">
                {{ $member->isSuspended() ? 'ยืนยันเปิดใช้งาน' : 'ยืนยันระงับสมาชิก' }}
            </button>
        </div>
    </dialog>

    {{-- Modal ยืนยัน ลบบัญชี --}}
    <dialog id="confirm-delete-member-modal" class="vault-dialog">
        <div class="vault-dialog-heading">
            <h2 class="text-xl font-bold flex items-center gap-2 text-rose-400">
                <span>🗑️</span> ยืนยันการลบบัญชีสมาชิก?
            </h2>
            <button type="button" data-close-dialog aria-label="ปิด">×</button>
        </div>
        <div class="mt-4 space-y-3 text-sm text-slate-300">
            <p>คุณกำลังจะลบบัญชีสมาชิก <strong class="text-white">{{ $member->name }}</strong> (<span class="text-slate-400">{{ $member->email }}</span>) ออกจากระบบ</p>
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 p-3.5 text-xs text-rose-200">
                <p class="font-semibold">⚠️ คำเตือนการลบบัญชี:</p>
                <p class="mt-1">การลบบัญชีไม่สามารถย้อนกลับได้ ข้อมูลสมาชิกจะถูกลบออกจากฐานข้อมูลอย่างถาวร (หากต้องการตัดสิทธิ์การใช้งานชั่วคราว แนะนำให้ใช้การระงับสมาชิกแทน)</p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" data-close-dialog class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium hover:bg-slate-800 transition">
                ยกเลิก
            </button>
            <form method="POST" action="{{ route('admin.members.destroy', $member) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-500 transition">
                    ยืนยันลบบัญชี
                </button>
            </form>
        </div>
    </dialog>
    @endif

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('member-form');
        const saveBtn = document.getElementById('save-member-btn');
        const modal = document.getElementById('confirm-save-modal');
        const confirmSubmit = document.getElementById('confirm-save-submit');

        const originalRole = @json($member->role);
        const originalStatus = @json($member->status ?? 'active');
        const originalEmail = @json($member->email);

        if (saveBtn && modal) {
            saveBtn.addEventListener('click', () => {
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const nameInput = form.querySelector('[name="name"]');
                const emailInput = form.querySelector('[name="email"]');
                const roleInput = form.querySelector('[name="role"]');
                const statusInput = form.querySelector('[name="status"]');

                const currentRole = roleInput ? roleInput.value : originalRole;
                const currentStatus = statusInput ? statusInput.value : originalStatus;
                const currentEmail = emailInput ? emailInput.value : originalEmail;

                const nameTarget = document.getElementById('summary-member-name');
                if (nameTarget && nameInput) nameTarget.textContent = nameInput.value;

                // Check Role change
                const roleWarning = document.getElementById('role-change-warning');
                const roleWarningText = document.getElementById('role-change-text');
                if (roleWarning && roleWarningText) {
                    if (currentRole !== originalRole) {
                        roleWarning.classList.remove('hidden');
                        if (currentRole === 'admin') {
                            roleWarningText.textContent = 'คุณกำลังปรับสิทธิ์เป็น "ผู้ดูแลระบบ (Admin)" ซึ่งบัญชีนี้จะสามารถเข้าถึง ดู และแก้ไขข้อมูลหลังบ้านทั้งหมดได้';
                        } else {
                            roleWarningText.textContent = 'คุณกำลังลดสิทธิ์เป็น "สมาชิกทั่วไป" บัญชีนี้จะไม่สามารถเข้าถึงระบบหลังบ้านได้อีกต่อไป';
                        }
                    } else {
                        roleWarning.classList.add('hidden');
                    }
                }

                // Check Status change
                const statusWarning = document.getElementById('status-change-warning');
                const statusWarningText = document.getElementById('status-change-text');
                if (statusWarning && statusWarningText) {
                    if (currentStatus !== originalStatus) {
                        statusWarning.classList.remove('hidden');
                        if (currentStatus === 'suspended') {
                            statusWarningText.textContent = 'คุณกำลังจะ "ระงับการใช้งาน" บัญชีนี้ สมาชิกจะไม่สามารถเข้าสู่ระบบหรือทำรายการใดๆ ในร้านได้';
                        } else {
                            statusWarningText.textContent = 'คุณกำลังจะ "เปิดใช้งาน" บัญชีนี้ สมาชิกจะสามารถกลับเข้าสู่ระบบได้ตามปกติ';
                        }
                    } else {
                        statusWarning.classList.add('hidden');
                    }
                }

                // Email change
                const emailLine = document.getElementById('summary-email-line');
                if (emailLine) {
                    if (currentEmail !== originalEmail) {
                        emailLine.classList.remove('hidden');
                        emailLine.textContent = `อีเมลใหม่: ${currentEmail} (จะต้องยืนยันอีเมลใหม่)`;
                    } else {
                        emailLine.classList.add('hidden');
                    }
                }

                const roleLine = document.getElementById('summary-role-line');
                if (roleLine) roleLine.textContent = `สิทธิ์: ${currentRole === 'admin' ? 'ผู้ดูแลระบบ' : 'สมาชิก'}`;

                const statusLine = document.getElementById('summary-status-line');
                if (statusLine) statusLine.textContent = `สถานะ: ${currentStatus === 'active' ? '🟢 เปิดใช้งาน' : '🔴 ระงับการใช้งาน'}`;

                modal.showModal();
            });

            if (confirmSubmit) {
                confirmSubmit.addEventListener('click', () => {
                    confirmSubmit.disabled = true;
                    confirmSubmit.textContent = 'กำลังบันทึก…';
                    form.submit();
                });
            }
        }
    });
    </script>
    @endif
</div>
@endsection
