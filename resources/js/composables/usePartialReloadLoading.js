import { ref, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Track loading state untuk partial reload Inertia di halaman tertentu.
 * Dipakai untuk trigger skeleton loader di tabel saat search/filter.
 *
 *   const { loading } = usePartialReloadLoading('/riwayat/semua');
 *
 *   <template v-if="loading">
 *       <tr v-for="n in 10" class="skeleton-row">...</tr>
 *   </template>
 *
 * @param {string} pathname URL pathname yang dipantau (mis. '/barang', '/stok')
 */
export function usePartialReloadLoading(pathname) {
    const loading = ref(false);

    const offStart = router.on('start', (e) => {
        if (e.detail.visit.url.pathname === pathname) loading.value = true;
    });
    const offFinish = router.on('finish', () => {
        loading.value = false;
    });

    onBeforeUnmount(() => {
        offStart();
        offFinish();
    });

    return { loading };
}
