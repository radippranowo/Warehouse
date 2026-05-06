<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">KATEGORI</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center gap-2">
                                <div class="search-box">
                                    <div class="position-relative">
                                        <input type="text" wire:model.live="search" class="form-control btn-rounded"
                                            placeholder="Cari..." style="padding-left: 40px;">
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
                                <button type="button" class="btn btn-success btn-rounded waves-effect waves-light mb-2"
                                    onclick="window.dispatchEvent(new CustomEvent('open-tambah-modal'))">
                                    <i class="mdi mdi-plus me-1"></i> Kategori
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-check">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20px;" class="align-middle">No</th>
                                    <th class="align-middle">Kode</th>
                                    <th class="align-middle">Nama</th>
                                    <th class="align-middle">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $index => $item)
                                    <tr>

                                        <td>{{ $categories->firstItem() + $index }}</td>
                                        <td>{{ $item->kode_category }}</td>
                                        <td>{{ $item->nama_category }}</td>
                                        <td>
                                            <div class="d-flex gap-3">
                                               <a class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16"
                                                wire:click="edit({{ $item->id }})">
                                            </a>
                                                <a class="btn btn-sm btn-soft-danger"
                                                    wire:click="$dispatch('confirm-delete-category', { id: {{ $item->id }}, nama: '{{ $item->nama_category }}' })">
                                                    <i class="mdi mdi-delete-outline"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $categories->links() }}
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
                        <h5>Form Categori</h5>
                        <button class="btn-close" @click="open=false"></button>
                    </div>

                    <form wire:submit.prevent="store">
                        <div class="modal-body">
                            <div class="row">

                                <div class="col-md-4 mb-3">
                                      <label for="kode_category" class="form-label">Kode</label>
                                    <input id="kode_category" name="kode_category" wire:model.live="kode_category"
                                        class="form-control @error('kode_category') is-invalid @enderror"
                                        placeholder="Kode">
                                    @error('kode_category')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                      <label for="nama_category" class="form-label">Nama</label>
                                    <input id="nama_category" name="nama_category" wire:model.live="nama_category"
                                        class="form-control @error('nama_category') is-invalid @enderror"
                                        placeholder="Nama">
                                    @error('nama_category')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
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
        // Listener Konfirmasi Hapus
        window.addEventListener('confirm-delete-category', event => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Kategori " + event.detail.nama + " akan dihapus permanen!",
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
    <script>
        // Tambahkan ini di bawah listener swal:success Anda
        window.addEventListener('swal:error', event => {
            Swal.fire({
                title: event.detail[0].title, // Mengambil data title dari array detail
                text: event.detail[0].text, // Mengambil data pesan error
                icon: 'error',
                confirmButtonColor: '#556ee6'
            });
        });
    </script>
</div>
