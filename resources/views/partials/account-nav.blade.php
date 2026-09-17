<nav class="account-nav" aria-label="เมนูบัญชี">
    <div class="account-nav-title"><span class="account-avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span class="hidden sm:inline">บัญชีของฉัน</span></div>
    <div class="account-nav-links">
        <a class="{{ request()->routeIs('user.dashboard') ? 'is-active' : '' }}" href="{{ route('user.dashboard') }}">ภาพรวม</a>
        <a class="{{ request()->routeIs('user.collection') ? 'is-active' : '' }}" href="{{ route('user.collection') }}">คอลเลกชัน</a>
        <a class="{{ request()->routeIs('wallet.*') ? 'is-active' : '' }}" href="{{ route('wallet.index') }}">Wallet</a>
        <a class="{{ request()->routeIs('orders.*','purchases.*') ? 'is-active' : '' }}" href="{{ route('orders.index') }}">ประวัติ</a>
        <a class="{{ request()->routeIs('notifications.*') ? 'is-active' : '' }}" href="{{ route('notifications.index') }}">แจ้งเตือน</a>
        <a class="{{ request()->routeIs('password.*') ? 'is-active' : '' }}" href="{{ route('password.edit') }}">รหัสผ่าน</a>
        <a href="{{ route('logout.form') }}">ออกจากระบบ</a>
    </div>
</nav>
