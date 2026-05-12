import { computed } from 'vue';

/**
 * Composable untuk form transaksi barang (masuk/keluar)
 * Menghindari duplikasi kode dan meningkatkan maintainability
 */
export function useTransaksiForm(form, barangs, tipeTransaksi = null) {
    // Cache barang lookup untuk performa O(1) vs O(n)
    const barangMap = computed(() => {
        const map = new Map();
        barangs.value.forEach(b => map.set(b.id, b));
        return map;
    });

    // Get barang info dengan caching
    function getBarangInfo(id) {
        const b = barangMap.value.get(parseInt(id));
        return b ? { 
            kode: b.kode_barang, 
            nama: b.nama_barang, 
            satuan: b.satuan,
            harga: b.harga 
        } : null;
    }

    // Validasi live per row dengan optimasi
    const rowErrors = computed(() => {
        const barangIds = new Set();
        return form.items.map((row) => {
            const errors = {};
            
            if (!row.barang_id) {
                errors.barang_id = 'Pilih barang';
            } else if (barangIds.has(row.barang_id)) {
                errors.barang_id = 'Barang sudah dipilih';
            } else {
                barangIds.add(row.barang_id);
            }
            
            if (!row.qty || row.qty <= 0) {
                errors.qty = 'Qty harus > 0';
            }
            
            // Validasi harga berbeda untuk barang masuk dan keluar
            if (tipeTransaksi === 'in') {
                // Barang masuk: harga harus > 0
                if (!row.harga_satuan || row.harga_satuan <= 0) {
                    errors.harga_satuan = 'Harga harus > 0';
                }
            } else {
                // Barang keluar: harga tidak boleh negatif
                if (row.harga_satuan < 0) {
                    errors.harga_satuan = 'Harga tidak boleh negatif';
                }
            }
            
            return errors;
        });
    });

    // Total qty dengan memoization
    const totalQty = computed(() => {
        return form.items.reduce((sum, row) => sum + (parseFloat(row.qty) || 0), 0);
    });

    // Total value dengan memoization
    const totalValue = computed(() => {
        return form.items.reduce((sum, row) => {
            const qty = parseFloat(row.qty) || 0;
            const harga = parseFloat(row.harga_satuan) || 0;
            return sum + (qty * harga);
        }, 0);
    });

    // Format currency helper
    function formatCurrency(value) {
        const rounded = Math.round(value || 0);
        return 'Rp ' + rounded.toLocaleString('id-ID', { 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0 
        });
    }

    // Format number helper
    function formatNumber(value) {
        return (value || 0).toLocaleString('id-ID');
    }

    return {
        barangMap,
        getBarangInfo,
        rowErrors,
        totalQty,
        totalValue,
        formatCurrency,
        formatNumber,
    };
}

/**
 * Keyboard shortcuts handler untuk form transaksi
 */
export function useTransaksiKeyboard(callbacks) {
    function handleKeyboard(e) {
        // Ctrl/Cmd + Enter = Submit
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (callbacks.onSubmit && !callbacks.disabled?.()) {
                e.preventDefault();
                callbacks.onSubmit();
            }
        }
        
        // Ctrl/Cmd + N = Tambah baris
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            if (callbacks.onAddRow) {
                callbacks.onAddRow();
            }
        }

        // Ctrl/Cmd + R = Reset form
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            if (callbacks.onReset) {
                callbacks.onReset();
            }
        }
    }

    return { handleKeyboard };
}

/**
 * Error handler untuk form submission
 */
export function handleFormErrors(errors) {
    console.error('Validation errors:', errors);
    
    // Tampilkan error pertama yang ditemukan
    const firstError = Object.entries(errors)[0];
    if (firstError) {
        const [key, message] = firstError;
        if (key.startsWith('items.')) {
            const match = key.match(/items\.(\d+)\.(\w+)/);
            if (match) {
                const idx = parseInt(match[1]) + 1;
                window.toast?.error(`Baris ${idx}: ${message}`);
            }
        } else {
            window.toast?.error(message);
        }
    }
}
