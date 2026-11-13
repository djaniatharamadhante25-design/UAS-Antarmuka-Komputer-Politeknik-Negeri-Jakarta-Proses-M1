@php
    $f = $filters;
    $from = $pg['total'] ? ($pg['page'] - 1) * $pg['per'] + 1 : 0;
    $to = $from ? $from + count($pg['rows']) - 1 : 0;
    $filtered = $f['q'] !== '' || $f['filter'] !== '';
@endphp
<div class="adm-tabs" role="tablist" aria-label="Jenis konten">
  <a href="{{ route('admin.section', ['section' => 'forum']) }}" class="{{ $tab === 'threads' ? 'is-active' : '' }}"{{ $tab === 'threads' ? ' aria-current="page"' : '' }}>Thread</a>
  <a href="{{ route('admin.section', ['section' => 'forum', 'tab' => 'replies']) }}" class="{{ $tab === 'replies' ? 'is-active' : '' }}"{{ $tab === 'replies' ? ' aria-current="page"' : '' }}>Balasan</a>
</div>

<div class="adm-toolbar">
  <form class="adm-filter" method="get" action="{{ route('admin.section', ['section' => 'forum']) }}" role="search">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="adm-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" name="q" value="{{ $f['q'] }}" placeholder="{{ $tab === 'threads' ? 'Cari judul thread' : 'Cari isi balasan' }}" aria-label="Cari"></label>
    <select name="filter" aria-label="Filter status">
      <option value="">Semua status</option>
      <option value="hidden" {{ $f['filter'] === 'hidden' ? 'selected' : '' }}>Disembunyikan</option>
      @if ($tab === 'threads')
          <option value="locked" {{ $f['filter'] === 'locked' ? 'selected' : '' }}>Dikunci</option>
      @endif
    </select>
    <button class="btn btn-sm btn-outline-light" type="submit">Terapkan</button>
    @if ($filtered)
        <a class="adm-reset" href="{{ route('admin.section', ['section' => 'forum', 'tab' => $tab === 'replies' ? 'replies' : '']) }}">Reset</a>
    @endif
  </form>
</div>

<div class="adm-card adm-card-flush">
  @if (count($pg['rows']) === 0)
    <p class="adm-empty">{{ $filtered ? 'Tidak ada yang cocok dengan filter ini.' : ($tab === 'threads' ? 'Belum ada thread di forum.' : 'Belum ada balasan di forum.') }}</p>
  @elseif ($tab === 'threads')
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Thread</th><th>Kategori</th><th class="num">Balasan</th><th>Status</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($pg['rows'] as $t)
        <tr class="{{ $t->is_hidden ? 'is-dim' : '' }}">
          <td data-label="Thread" class="adm-wrap">
            <a class="adm-title" href="{{ route('thread', ['id' => (int) $t->id]) }}" target="_blank" rel="noopener">{{ $t->title }}</a>
            <small>oleh {{ $t->username }} · {{ App\Support\AdminPresenter::ago((string) $t->created_at) }}</small>
          </td>
          <td data-label="Kategori">{{ $t->category_name }}</td>
          <td class="num" data-label="Balasan">{{ (int) $t->reply_count }}</td>
          <td data-label="Status">
            <span class="adm-badge {{ $t->is_hidden ? 'is-warn' : 'is-ok' }}">{{ $t->is_hidden ? 'Disembunyikan' : 'Tampil' }}</span>
            @if ($t->is_locked)
                <span class="adm-badge is-muted"><i class="fa-solid fa-lock" aria-hidden="true"></i> Dikunci</span>
            @endif
          </td>
          <td class="end">
            <div class="adm-actions">
              <form method="post" action="{{ route('admin.action') }}">@csrf<input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
                <input type="hidden" name="action" value="admin_thread_hide"><input type="hidden" name="thread_id" value="{{ (int) $t->id }}">
                <button class="btn btn-sm btn-outline-light" type="submit"><i class="fa-solid {{ $t->is_hidden ? 'fa-eye' : 'fa-eye-slash' }}" aria-hidden="true"></i> {{ $t->is_hidden ? 'Tampilkan' : 'Sembunyikan' }}</button></form>
              <form method="post" action="{{ route('admin.action') }}">@csrf<input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
                <input type="hidden" name="action" value="admin_thread_lock"><input type="hidden" name="thread_id" value="{{ (int) $t->id }}">
                <button class="btn btn-sm btn-outline-light" type="submit"><i class="fa-solid {{ $t->is_locked ? 'fa-lock-open' : 'fa-lock' }}" aria-hidden="true"></i> {{ $t->is_locked ? 'Buka' : 'Kunci' }}</button></form>
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @else
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Balasan</th><th>Thread</th><th>Status</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($pg['rows'] as $r)
        <tr class="{{ $r->is_hidden ? 'is-dim' : '' }}">
          <td data-label="Balasan" class="adm-wrap">
            <span class="adm-snippet">{{ mb_strimwidth((string) $r->body, 0, 140, '…') }}</span>
            <small>oleh {{ $r->username }} · {{ App\Support\AdminPresenter::ago((string) $r->created_at) }}</small>
          </td>
          <td data-label="Thread" class="adm-wrap"><a href="{{ route('thread', ['id' => (int) $r->thread_id]) }}" target="_blank" rel="noopener">{{ $r->thread_title }}</a></td>
          <td data-label="Status"><span class="adm-badge {{ $r->is_hidden ? 'is-warn' : 'is-ok' }}">{{ $r->is_hidden ? 'Disembunyikan' : 'Tampil' }}</span></td>
          <td class="end">
            <div class="adm-actions">
              <form method="post" action="{{ route('admin.action') }}">@csrf<input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
                <input type="hidden" name="action" value="admin_reply_hide"><input type="hidden" name="reply_id" value="{{ (int) $r->id }}">
                <button class="btn btn-sm btn-outline-light" type="submit"><i class="fa-solid {{ $r->is_hidden ? 'fa-eye' : 'fa-eye-slash' }}" aria-hidden="true"></i> {{ $r->is_hidden ? 'Tampilkan' : 'Sembunyikan' }}</button></form>
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
  @if (count($pg['rows']) > 0)
  <div class="adm-tablefoot"><span>Menampilkan {{ $from }}–{{ $to }} dari {{ (int) $pg['total'] }} {{ $tab === 'threads' ? 'thread' : 'balasan' }}</span>{!! App\Support\AdminPresenter::pager($pg, ['section' => 'forum'] + array_filter($f)) !!}</div>
  @endif
</div>
