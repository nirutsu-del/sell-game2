<nav class="admin-nav" aria-label="เมนูจัดการร้าน">
    <div class="admin-nav-title"><span class="admin-dot"></span><span>จัดการร้าน</span></div>
    <div class="admin-nav-links">
        <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">ภาพรวม</a>
        <a class="{{ request()->routeIs('admin.accounts.*') ? 'is-active' : '' }}" href="{{ route('admin.accounts.index') }}">ไอดีเกม</a>
        <a class="{{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}" href="{{ route('admin.categories.index') }}">หมวดหมู่</a>
        @if(config('store.service_catalog_enabled'))
        <a class="{{ request()->routeIs('admin.products.*','admin.service-orders.*') ? 'is-active' : '' }}" href="{{ route('admin.products.index') }}">สินค้า / งานบริการ</a>
        @endif
        <a class="{{ request()->routeIs('admin.gacha.*') ? 'is-active' : '' }}" href="{{ route('admin.gacha.index') }}">กล่องสุ่ม</a>
        <a class="{{ request()->routeIs('admin.topups.*') ? 'is-active' : '' }}" href="{{ route('admin.topups.index') }}">เติมเงิน</a>
        <a class="{{ request()->routeIs('admin.contacts.*') ? 'is-active' : '' }}" href="{{ route('admin.contacts.index') }}">ข้อความ</a>
        <a class="{{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.reports.sales') }}">รายงาน</a>
        <a class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}">ตั้งค่าร้าน</a>
        <a class="{{ request()->routeIs('password.*') ? 'is-active' : '' }}" href="{{ route('password.edit') }}">เปลี่ยนรหัสผ่าน</a>
        <a href="{{ route('logout.form') }}">ออกจากระบบ</a>
    </div>
</nav>
