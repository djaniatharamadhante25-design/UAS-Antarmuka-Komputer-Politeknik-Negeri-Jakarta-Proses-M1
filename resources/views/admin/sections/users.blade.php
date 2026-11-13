@php
    $f = $filters;
    $from = $pg['total'] ? ($pg['page'] - 1) * $pg['per'] + 1 : 0;
    $to = $from ? $from + count($pg['rows']) - 1 : 0;
    $meId = auth()->id();
    $usersFilter = array_filter($f);
@endphp
<div class="adm-toolbar">
  <form class="adm-filter" method="get" action="{{ route('admin.section', ['section' => 'users']) }}" role="search">
    <label class="adm-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" name="q" value="{{ $f['q'] }}" placeholder="Cari username atau email" aria-label="Cari pengguna"></label>
    <select name="role" aria-label="Filter role">
      <option value="">Semua role</option>
      <option value="admin" {{ $f['role'] === 'admin' ? 'selected' : '' }}>Admin</option>
      <option value="user" {{ $f['role'] === 'user' ? 'selected' : '' }}>User</option>
    </select>
    <button class="btn btn-sm btn-outline-light" type="submit">Terapkan</button>
    @if ($f['q'] !== '' || $f['role'] !== '')
        <a class="adm-reset" href="{{ route('admin.section', ['section' => 'users']) }}">Reset</a>
    @endif
  </form>
</div>

<details class="adm-form" {{ $edit ? 'open' : '' }}>
  <summary class="btn btn-primary btn-sm"><i class="fa-solid {{ $edit ? 'fa-pen' : 'fa-plus' }}" aria-hidden="true"></i> {{ $edit ? 'Edit pengguna: ' . $edit->username : 'Tambah pengguna' }}</summary>
  <form method="post" action="{{ route('admin.action') }}" class="adm-fields">
    @csrf
    <input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
    <input type="hidden" name="action" value="admin_user_save">
    <input type="hidden" name="user_id" value="{{ (int) ($edit->id ?? 0) }}">
    <label>Username<input name="username" required maxlength="50" pattern="[A-Za-z0-9_]{3,50}" title="3-50 karakter: huruf, angka, underscore" value="{{ $edit->username ?? '' }}"></label>
    <label>Email<input type="email" name="email" required maxlength="150" value="{{ $edit->email ?? '' }}"></label>
    <label>Role
      <select name="role">
        <option value="user" {{ ($edit->role ?? 'user') === 'user' ? 'selected' : '' }}>User</option>
        <option value="admin" {{ ($edit->role ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
      </select>
    </label>
    <label>Password<input type="password" name="password" minlength="6" autocomplete="new-password" {{ $edit ? '' : 'required' }} placeholder="{{ $edit ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' }}"></label>
    <div class="adm-fields-actions">
      <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
      @if ($edit)
          <a class="btn btn-outline-light btn-sm" href="{{ route('admin.section', $usersFilter + ['section' => 'users']) }}">Batal</a>
      @endif
    </div>
  </form>
</details>

<div class="adm-card adm-card-flush">
  @if (count($pg['rows']) === 0)
    <p class="adm-empty">Tidak ada pengguna yang cocok. <a href="{{ route('admin.section', ['section' => 'users']) }}">Tampilkan semua</a></p>
  @else
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Pengguna</th><th>Role</th><th class="num">My List</th><th class="num">Thread</th><th>Bergabung</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($pg['rows'] as $u)
          @php $isMe = (int) $u->id === $meId; @endphp
        <tr>
          <td data-label="Pengguna">
            <div class="adm-person">
              <span class="mini-avatar">{{ strtoupper(substr($u->username, 0, 1)) }}</span>
              <div><strong>{{ $u->username }}</strong>@if ($isMe)<span class="adm-badge is-muted">Kamu</span>@endif<small>{{ $u->email }}</small></div>
            </div>
          </td>
          <td data-label="Role"><span class="adm-badge {{ $u->role === 'admin' ? 'is-admin' : '' }}">{{ $u->role === 'admin' ? 'Admin' : 'User' }}</span></td>
          <td class="num" data-label="My List">{{ (int) $u->list_count }}</td>
          <td class="num" data-label="Thread">{{ (int) $u->thread_count }}</td>
          <td data-label="Bergabung"><time title="{{ (string) $u->created_at }}">{{ App\Support\AdminPresenter::ago((string) $u->created_at) }}</time></td>
          <td class="end">
            <div class="adm-actions">
              <a class="btn btn-sm btn-outline-light" href="{{ route('admin.section', ['section' => 'users', 'edit' => (int) $u->id] + $usersFilter) }}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
              @if (! $isMe)
              <form method="post" action="{{ route('admin.action') }}" data-confirm="Hapus pengguna {{ $u->username }}? List dan thread forum miliknya ikut terhapus. Tindakan ini tidak bisa dibatalkan.">
                @csrf
                <input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
                <input type="hidden" name="action" value="admin_user_delete"><input type="hidden" name="user_id" value="{{ (int) $u->id }}">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Hapus</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="adm-tablefoot"><span>Menampilkan {{ $from }}–{{ $to }} dari {{ (int) $pg['total'] }} pengguna</span>{!! App\Support\AdminPresenter::pager($pg, ['section' => 'users'] + $usersFilter) !!}</div>
  @endif
</div>
