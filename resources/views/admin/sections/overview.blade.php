@php
    $stats = [
        ['Pengguna', $counts['users'], '+' . $counts['users_week'] . ' minggu ini · ' . $counts['admins'] . ' admin', $counts['users_week'], 'fa-users'],
        ['Entri My List', $counts['lists'], '+' . $counts['lists_week'] . ' minggu ini', $counts['lists_week'], 'fa-bookmark'],
        ['Thread forum', $counts['threads'], '+' . $counts['threads_week'] . ' minggu ini · ' . $counts['replies'] . ' balasan', $counts['threads_week'], 'fa-comments'],
        ['Link tonton/baca', $counts['links'], $counts['categories'] . ' kategori forum', 0, 'fa-link'],
    ];
    $statusMax = $statuses->isNotEmpty() ? $statuses->max('total') : 1;
@endphp
<div class="adm-stats">
  @foreach ($stats as [$label, $value, $sub, $delta, $icon])
    <div class="adm-stat">
      <div class="adm-stat-top"><span>{{ $label }}</span><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div>
      <strong>{{ (int) $value }}</strong>
      <small class="{{ $delta > 0 ? 'is-up' : '' }}">{{ $sub }}</small>
    </div>
  @endforeach
</div>

<div class="adm-grid adm-grid-wide">
  <div class="adm-card">
    <div class="adm-card-head">
      <h2>Aktivitas 14 hari terakhir</h2>
      <div class="adm-legend"><span class="lg-a">Pengguna baru</span><span class="lg-b">Entri My List baru</span></div>
    </div>
    @if (array_sum($chart_users) + array_sum($chart_lists) === 0)
      <p class="adm-empty">Belum ada pendaftaran atau entri list dalam 14 hari terakhir.</p>
    @else
      {!! App\Support\AdminPresenter::chart($chart_days, $chart_users, $chart_lists, 'pengguna baru', 'entri list') !!}
    @endif
  </div>

  <div class="adm-card">
    <div class="adm-card-head"><h2>Perlu dicek</h2></div>
    <ul class="adm-todo">
      <li>
        <a href="{{ route('admin.section', ['section' => 'forum', 'filter' => 'hidden']) }}">
          <i class="fa-solid fa-eye-slash" aria-hidden="true"></i><span>Thread disembunyikan</span><b>{{ (int) $counts['threads_hidden'] }}</b>
        </a>
      </li>
      <li>
        <a href="{{ route('admin.section', ['section' => 'forum', 'filter' => 'locked']) }}">
          <i class="fa-solid fa-lock" aria-hidden="true"></i><span>Thread dikunci</span><b>{{ (int) $counts['threads_locked'] }}</b>
        </a>
      </li>
      <li>
        <a href="{{ route('admin.section', ['section' => 'forum', 'tab' => 'replies', 'filter' => 'hidden']) }}">
          <i class="fa-solid fa-reply" aria-hidden="true"></i><span>Balasan disembunyikan</span><b>{{ (int) $counts['replies_hidden'] }}</b>
        </a>
      </li>
      <li>
        <a href="{{ route('admin.section', ['section' => 'users', 'role' => 'admin']) }}">
          <i class="fa-solid fa-user-shield" aria-hidden="true"></i><span>Akun admin aktif</span><b>{{ (int) $counts['admins'] }}</b>
        </a>
      </li>
      <li>
        <a href="{{ route('admin.section', ['section' => 'links']) }}" title="Lihat katalog database lokal">
          <i class="fa-solid fa-cloud-arrow-down text-info" aria-hidden="true"></i><span>Katalog Database Lokal</span><b>{{ (int) ($counts['catalog_titles'] ?? 0) }} judul</b>
        </a>
      </li>
    </ul>
  </div>
</div>

<div class="adm-grid">
  <div class="adm-card">
    <div class="adm-card-head"><h2>Status di My List</h2><small>{{ (int) $counts['lists'] }} entri</small></div>
    @if ($statuses->isEmpty())
      <p class="adm-empty">Belum ada pengguna yang menyimpan anime atau manga ke My List.</p>
    @else
      <ul class="adm-bars">
        @foreach ($statuses as $row)
          <li>
            <span>{{ App\Support\OtakuHelper::statusLabel((string) $row->status) }}</span>
            <div class="adm-bar" role="presentation"><i style="width:{{ max(3, round($row->total / $statusMax * 100)) }}%"></i></div>
            <b>{{ (int) $row->total }}</b>
          </li>
        @endforeach
      </ul>
    @endif
  </div>

  <div class="adm-card">
    <div class="adm-card-head"><h2>Judul paling banyak disimpan</h2></div>
    @if ($top->isEmpty())
      <p class="adm-empty">Daftar muncul setelah ada pengguna yang menambahkan judul ke My List.</p>
    @else
      <ol class="adm-top">
        @foreach ($top as $t)
          <li>
            <img src="{{ App\Support\OtakuHelper::imageUrl($t->image_url) }}" alt="" loading="lazy" width="38" height="54">
            <div>
              <a href="{{ App\Support\OtakuHelper::mediaDetailUrl((string) $t->media_type, (int) $t->external_id, 0) }}">{{ $t->title }}</a>
              <small>{{ ucfirst((string) $t->media_type) }}{{ $t->avg_score !== null ? ' · rata-rata skor ' . number_format((float) $t->avg_score, 1) : '' }}</small>
            </div>
            <b>{{ (int) $t->total }}<small>pengguna</small></b>
          </li>
        @endforeach
      </ol>
    @endif
  </div>
</div>

<div class="adm-card">
  <div class="adm-card-head"><h2>Aktivitas terbaru</h2></div>
  @if ($activity->isEmpty())
    <p class="adm-empty">Belum ada aktivitas.</p>
  @else
    <ul class="adm-feed">
      @foreach ($activity as $a)
          @php
              $icon = ['user' => 'fa-user-plus', 'thread' => 'fa-comment', 'reply' => 'fa-reply', 'list' => 'fa-bookmark'][$a->kind] ?? 'fa-circle';
              $href = match ($a->kind) {
                  'thread', 'reply' => route('thread', ['id' => (int) $a->ref_id]),
                  'user' => route('admin.section', ['section' => 'users', 'q' => $a->actor]),
                  default => '',
              };
          @endphp
        <li>
          <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
          <p>
            <strong>{{ $a->actor }}</strong>
            @if ($a->kind === 'user')
                bergabung
            @elseif ($a->kind === 'thread')
                membuat thread <a href="{{ $href }}">{{ $a->label }}</a>
            @elseif ($a->kind === 'reply')
                membalas <a href="{{ $href }}">{{ $a->label }}</a>
            @else
                menyimpan <em>{{ $a->label }}</em> ke My List
            @endif
          </p>
          <time datetime="{{ (string) $a->created_at }}" title="{{ (string) $a->created_at }}">{{ App\Support\AdminPresenter::ago((string) $a->created_at) }}</time>
        </li>
      @endforeach
    </ul>
  @endif
</div>
