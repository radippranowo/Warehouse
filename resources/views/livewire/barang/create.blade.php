<div>
    <div x-cloak x-data="{
        inputs: @entangle('inputs').live,
    
        add() {
            this.inputs.push({
                id: 'new-' + Date.now(),
                kode_barang: '',
                part_number: '',
                nama_barang: '',
                category_code: '',
                merk_code: '',
                group_code: '',
                stok: 0,
                harga: 0,
            });
        },
    
        remove(index) {
            if (this.inputs.length > 1) {
                this.inputs.splice(index, 1);
            }
        }
    }">
        <div class="card shadow-sm">
            <div class="card-body border-bottom d-flex justify-content-between">
                <h5 class="mb-0">Barang</h5>

                <a href="{{ route('barang.index') }}" wire:navigate
                    class="btn btn-primary btn-rounded waves-effect waves-light mb-2">
                    <i class="mdi mdi-arrow-left me-1"></i>Kembali

                </a>
            </div>

            <div class="card-body">
                <form wire:submit.prevent="save">

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">Kode<span class="text-danger"></span></th>
                                    <th style="width: 15%;">Part Number<span class="text-danger"></span></th>
                                    <th style="width: 15%;">Nama<span class="text-danger"></span></th>
                                    <th style="width: 15%;">Kategori <span class="text-danger"></span></th>
                                    <th style="width: 15%;">Merk <span class="text-danger"></span></th>
                                    <th style="width: 15%;">Group <span class="text-danger"></span></th>
                                    <th style="width: 8%;">Stok</th>
                                    <th style="width: 15%;">Harga</th>
                                    {{-- <th style="width: 20%;">Deskripsi</th>  --}}
                                    <th style="width: 5%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-for="(item, index) in inputs" :key="item.id">
                                    <tr>
                                        <!-- KODE -->
                                        <td class="position-relative">
                                            <input type="text" :id="'kode_barang_' + item.id"
                                                x-model="item.kode_barang" class="form-control form-control-sm"
                                                :class="{ 'is-invalid': $wire.errors.has(`inputs.${index}.kode_barang`) }">

                                            <template x-if="$wire.errors.has(`inputs.${index}.kode_barang`)">
                                                <div class="invalid-feedback-absolute"
                                                    x-text="$wire.errors.get(`inputs.${index}.kode_barang`)[0]">
                                                </div>
                                            </template>
                                        </td>

                                        <!-- PART -->
                                        <td class="position-relative">
                                            <input type="text" :id="'part_number_' + item.id"
                                                x-model="item.part_number" class="form-control form-control-sm"
                                                :class="{ 'is-invalid': $wire.errors.has(`inputs.${index}.part_number`) }">

                                            <template x-if="$wire.errors.has(`inputs.${index}.part_number`)">
                                                <div class="invalid-feedback-absolute"
                                                    x-text="$wire.errors.get(`inputs.${index}.part_number`)[0]">
                                                </div>
                                            </template>
                                        </td>

                                        <!-- NAMA -->
                                        <td class="position-relative">
                                            <input type="text" :id="'nama_barang_' + item.id"
                                                x-model="item.nama_barang" class="form-control form-control-sm"
                                                :class="{ 'is-invalid': $wire.errors.has(`inputs.${index}.nama_barang`) }">

                                            <template x-if="$wire.errors.has(`inputs.${index}.nama_barang`)">
                                                <div class="invalid-feedback-absolute"
                                                    x-text="$wire.errors.get(`inputs.${index}.nama_barang`)[0]">
                                                </div>
                                            </template>
                                        </td>

                                        <!-- CATEGORY -->
                                        <td class="position-relative">
                                            <select :id="'category_' + item.id" x-model="item.category_code"
                                                class="form-select form-select-sm"
                                                :class="{ 'is-invalid': $wire.errors.has(`inputs.${index}.category_code`) }">

                                                <option value="">Pilih</option>
                                                @foreach ($categories as $cat)
                                                    <option value="{{ $cat->kode_category }}">
                                                        {{ $cat->nama_category }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <template x-if="$wire.errors.has(`inputs.${index}.category_code`)">
                                                <div class="invalid-feedback-absolute"
                                                    x-text="$wire.errors.get(`inputs.${index}.category_code`)[0]">
                                                </div>
                                            </template>  
                                        </td>

                                        <!-- MERK -->
                                        <td class="position-relative">
                                            <select :id="'merk_' + item.id" x-model="item.merk_code"
                                                class="form-select form-select-sm"
                                                :class="{ 'is-invalid': $wire.errors.has(`inputs.${index}.merk_code`) }">

                                                <option value="">Pilih</option>
                                                @foreach ($merks as $merk)
                                                    <option value="{{ $merk->kode_merk }}">
                                                        {{ $merk->nama_merk }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <template x-if="$wire.errors.has(`inputs.${index}.merk_code`)">
                                                <div class="invalid-feedback-absolute"
                                                    x-text="$wire.errors.get(`inputs.${index}.merk_code`)[0]">
                                                </div>
                                            </template>
                                        </td>

                                        <!-- GROUP -->
                                        <td class="position-relative">
                                            <select :id="'group_' + item.id" x-model="item.group_code"
                                                class="form-select form-select-sm"
                                                :class="{ 'is-invalid': $wire.errors.has(`inputs.${index}.group_code`) }">

                                                <option value="">Pilih</option>
                                                @foreach ($groups as $group)
                                                    <option value="{{ $group->kode_group }}">
                                                        {{ $group->nama_group }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <template x-if="$wire.errors.has(`inputs.${index}.group_code`)">
                                                <div class="invalid-feedback-absolute"
                                                    x-text="$wire.errors.get(`inputs.${index}.group_code`)[0]">
                                                </div>
                                            </template>
                                        </td>

                                        <!-- STOK -->
                                        <td class="position-relative">
                                            <input type="number" :id="'stok_' + item.id" x-model.number="item.stok"
                                                class="form-control form-control-sm text-center">
                                        </td>

                                        <!-- HARGA -->
                                        <td class="position-relative">
                                            <input type="number" :id="'harga_' + item.id" x-model.number="item.harga"
                                                class="form-control form-control-sm text-center">
                                        </td>

                                        <!-- AKSI -->
                                       <td class="position-relative">
                                            <button type="button" @click="remove(index)" x-show="inputs.length > 1"
                                                class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16">
                                            </button>
                                        </td>

                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between">
                        <button type="button" @click="add()"
                            class="btn btn-success btn-rounded waves-effect waves-light mb-2">
                            <i class="bx bx-plus label-icon"></i> Baris
                        </button>

                        <button type="submit" class="btn btn-success btn-rounded waves-effect waves-light mb-2">
                            <i class="bx bx-save label-icon"></i>
                            Simpan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
    <style>
        td.position-relative {
            padding-bottom: 18px !important;
        }

        .invalid-feedback-absolute {
            position: absolute;
            bottom: 2px;
            left: 8px;
            font-size: 10px;
            color: red;
        }

        .is-invalid {
            border-color: red !important;
        }
    </style>
</div>
