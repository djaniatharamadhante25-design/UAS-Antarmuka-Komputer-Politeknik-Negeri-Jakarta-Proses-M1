<div class="adm-empty d-none"></div>
<details class="adm-form" {{ $edit ? 'open' : '' }}>
  <summary class="btn btn-primary btn-sm"><i class="fa-solid {{ $edit ? 'fa-pen' : 'fa-plus' }}" aria-hidden="true"></i> {{ $edit ? 'Edit kategori: ' . $edit->name : 'Tambah kategori' }}</summary>
  <form method="post" action="{{ route('admin.action') }}" class="adm-fields">
    @csrf
    <input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
    <input type="hidden" name="action" value="admin_category_save">
    <input type="hidden" name="category_id" value="{{ (int) ($edit->id ?? 0) }}">
    <label>Nama<input name="name" required maxlength="80" value="{{ $edit->name ?? '' }}"></label>
    <label class="wide">Deskripsi<input name="description" maxlength="255" value="{{ $edit->description ?? '' }}" placeholder="Satu kalimat tentang topik kategori ini"></label>
    <div class="adm-fields-actions">
      <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
      @if ($edit)
          <a class="btn btn-outline-light btn-sm" href="{{ route('admin.section', ['section' => 'categories']) }}">Batal</a>
      @endif
    </div>
  </form>
</details>

<div class="adm-card adm-card-flush">
  @if (count($rows) === 0)
    <p class="adm-empty">Belum ada kategori. Tanpa kategori, pengguna tidak bisa membuat thread.</p>
  @else
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Kategori</th><th>Deskripsi</th><th class="num">Thread</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($rows as $row)
        <tr>
          <td data-label="Kategori"><strong>{{ $row->name }}</strong></td>
          <td data-label="Deskripsi" class="adm-wrap">{{ $row->description ?: '—' }}</td>
          <td class="num" data-label="Thread">{{ (int) $row->thread_count }}</td>
          <td class="end">
            <div class="adm-actions">
              <a class="btn btn-sm btn-outline-light" href="{{ route('admin.section', ['section' => 'categories', 'edit' => (int) $row->id]) }}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
              @if ((int) $row->thread_count === 0)
              <form method="post" action="{{ route('admin.action') }}" data-confirm="Hapus kategori {{ $row->name }}?">
                @csrf
                <input type="hidden" name="back" value="{{ App\Support\AdminPresenter::currentQuery(request()) }}">
                <input type="hidden" name="action" value="admin_category_delete"><input type="hidden" name="category_id" value="{{ (int) $row->id }}">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Hapus</button>
              </form>
              @else
              <span class="adm-hint" title="Kategori yang masih punya thread tidak bisa dihapus">Dipakai thread</span>
              @endif
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>
