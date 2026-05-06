<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">BARANG</h5>
                        <div class="flex-shrink-0">
                            <a wire:navigate.hover href="{{ route('barang.create') }}"
                                class="btn btn-success btn-rounded">
                                <i class="mdi mdi-plus me-1"></i>Barang
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center gap-2">
                                <div class="search-box">
                                    <div class="position-relative">
                                        <input id="search" name="search" wire:model.live.debounce.500ms="search"
                                            type="text" class="form-control btn-rounded" placeholder="Cari barang..."
                                            style="padding-left: 40px;">
                                        <i class="bx bx-search-alt search-icon" style="left: 13px;"></i>
                                    </div>
                                </div>

                                <div class="dropdown custom-no-anim">
                                    <button class="btn btn-light btn-rounded shadow-sm border dropdown-toggle"
                                        type="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false" style="min-width: 70px; transition: none;">
                                        {{ $perPage }} <i class="mdi mdi-chevron-down ms-1"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow rounded-4 border-0 mt-2"
                                        style="transition: none; min-width: 60px; position: absolute; z-index: 1000;">
                                        <li><a class="dropdown-item rounded-3" href="javascript:void(0);"
                                                wire:click="$set('perPage', 5)" style="transition: none;">5</a></li>
                                        <li><a class="dropdown-item rounded-3" href="javascript:void(0);"
                                                wire:click="$set('perPage', 10)" style="transition: none;">10</a></li>
                                        <li><a class="dropdown-item rounded-3" href="javascript:void(0);"
                                                wire:click="$set('perPage', 25)" style="transition: none;">50</a></li>
                                        <li><a class="dropdown-item rounded-3" href="javascript:void(0);"
                                                wire:click="$set('perPage', 50)" style="transition: none;">100</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-sm-end">
                                <button type="button"
                                    class="btn btn-success btn-rounded waves-effect waves-light mb-2">
                                    <i class="mdi mdi-plus me-1"></i> Filter
                                </button>
                            </div>
                        </div>

                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-check">
                            <thead class="table-light">
                                <tr>
                                    <th class="align-middle" style="width: 50px;">No</th>
                                    <th class="align-middle">Kode</th>
                                    <th class="align-middle">Part Number</th>
                                    <th class="align-middle">Nama</th>
                                    <th class="align-middle">Kategori</th>
                                    <th class="align-middle">Merk</th>
                                    <th class="align-middle">Group</th>
                                    <th class="align-middle">Stok</th>
                                    <th class="align-middle">Harga</th>
                                    {{-- <th class="align-middle">Deskripsi</th> --}}
                                    <th class="align-middle">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barangs as $item)
                                    <tr>
                                        {{-- Jika pakai pagination, gunakan rumus ini agar nomor berlanjut di halaman berikutnya --}}
                                        <td>{{ ($barangs->currentPage() - 1) * $barangs->perPage() + $loop->iteration }}
                                        </td>
                                        <td>{{ $item->kode_barang }}</td>
                                        <td>{{ $item->part_number }}</td>
                                        <td>{{ $item->nama_barang }}</td>
                                        <td>{{ $item->kategori->nama_category }}</td>
                                        <td>{{ $item->merk->nama_merk }}</td>
                                        <td>{{ $item->group->nama_group }}</td>
                                        <td><span class="badge badge-pill badge-soft-info font-size-12">
                                                {{ $item->stok ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                                        {{-- <td> {{ $item->deskripsi }}</td> --}}
                                        <td>
                                            <a class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16"
                                                wire:click="edit({{ $item->id }})">
                                            </a>
                                            <a class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16"
                                                wire:click="$dispatch('confirm-delete', { id: {{ $item->id }}, nama: '{{ $item->nama_barang }}' })">

                                            </a>

                                        </td>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ open: false }" x-init="window.addEventListener('open-tambah-modal', () => open = true);
    window.addEventListener('close-modal', () => open = false);" x-cloak wire:ignore.self>

        <div class="modal fade modal-blur" :class="open ? 'show d-block' : 'd-none'" x-show="open"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            style="background-color: rgba(0,0,0,0.5); z-index: 1060;" @click.self="open = false"
            @keydown.escape.window="open = false">

            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5>Form Barang</h5>
                        <button class="btn-close" @click="open=false"></button>
                    </div>

                    <form wire:submit.prevent="store">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="kode_barang" class="form-label">Kode</label>
                                    <input id="kode_barang" name="kode_barang" wire:model.live="kode_barang"
                                        class="form-control @error('kode_barang') is-invalid @enderror"
                                        placeholder="Kode">
                                    @error('kode_barang')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="part_number" class="form-label">Part Number</label>
                                    <input id="part_number" name="part_number" wire:model.live="part_number"
                                        class="form-control" placeholder="Part">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="nama_barang" class="form-label">Nama</label>
                                    <input id="nama_barang" name="nama_barang" wire:model.live="nama_barang"
                                        class="form-control @error('nama_barang') is-invalid @enderror"
                                        placeholder="Nama">
                                    @error('nama_barang')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <!-- SELECT2 -->
                                <div class="col-md-4 mb-3" wire:ignore>
                                    <label for="category_code" class="form-label">Category</label>
                                    <select id="category_code" wire:model="category_code"
                                        class="form-control select2" @if ($barang_id) disabled @endif>
                                        <option value="" disabled {{ $barang_id ? 'hidden' : '' }}>Pilih
                                        </option>

                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->kode_category }}">
                                                {{ $cat->nama_category }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if ($barang_id)
                                        <input type="hidden" wire:model="category_code">
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3" wire:ignore>
                                    <label for="merk_code" class="form-label">Merk</label>
                                    <select id="merk_code" wire:model="merk_code" class="form-control select2"
                                        @if ($barang_id) disabled @endif>
                                        <option value="" disabled {{ $barang_id ? 'hidden' : '' }}>Pilih
                                        </option>

                                        @foreach ($merks as $m)
                                            <option value="{{ $cat->kode_merk }}">
                                                {{ $m->nama_merk }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if ($barang_id)
                                        <input type="hidden" wire:model="merk_code">
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3" wire:ignore>
                                    <label for="group_code" class="form-label">Group</label>
                                    <select id="group_code" wire:model="group_code" class="form-control select2"
                                        @if ($barang_id) disabled @endif>
                                        <option value="" disabled {{ $barang_id ? 'hidden' : '' }}>Pilih
                                        </option>

                                        @foreach ($groups as $g)
                                            <option value="{{ $g->kode_group }}">
                                                {{ $g->nama_group }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if ($barang_id)
                                        <input type="hidden" wire:model="group_code">
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="stok" class="form-label">Stok</label>
                                    <input type="number" id="stok" name="stok" wire:model.live="stok"
                                        class="form-control" placeholder="Stok">
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label for="harga" class="form-label">Harga</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" id="harga" name="harga" wire:model.live="harga"
                                            class="form-control">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <textarea id="deskrpsi" name="deskripsi" wire:model.live="deskripsi" class="form-control"></textarea>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="open=false">Close</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('livewire:init', () => {

            $('#category_code').on('change', function() {
                Livewire.dispatch('setCategory', $(this).val());
            });

            $('#merk_code').on('change', function() {
                Livewire.dispatch('setMerk', $(this).val());
            });

            $('#group_code').on('change', function() {
                Livewire.dispatch('setGroup', $(this).val());
            });

        });
    </script>

    <script>
        // Listener Konfirmasi Hapus
        window.addEventListener('confirm-delete', event => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Barang " + event.detail.nama + " akan dihapus permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#34c38f', // warna sukses bootstrap
                cancelButtonColor: '#f46a6a', // warna danger bootstrap
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Memanggil fungsi delete di Class Livewire
                    @this.call('delete', event.detail.id);
                }
            });
        });

        // Listener Sukses (Bisa digunakan untuk Save/Edit/Delete)
        window.addEventListener('swal:success', event => {
            Swal.fire({
                title: 'Berhasil!',
                text: event.detail.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>
    @if (session()->has('success'))
        <script>
            Swal.fire({
                title: "Berhasil!",
                text: "{{ session('success') }}",
                icon: "success"
            });
        </script>
    @endif
</div>
