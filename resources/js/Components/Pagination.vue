<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: { type: Array, required: true },
});

// Optimasi pagination: hanya tampilkan beberapa halaman di sekitar halaman aktif
const optimizedLinks = computed(() => {
    if (!props.links || props.links.length <= 3) return props.links;

    const allLinks = props.links;
    const prevLink = allLinks[0]; // "Previous"
    const nextLink = allLinks[allLinks.length - 1]; // "Next"
    const pageLinks = allLinks.slice(1, -1); // Halaman angka

    if (pageLinks.length <= 7) {
        return allLinks; // Jika halaman sedikit, tampilkan semua
    }

    // Cari halaman aktif
    const activeIndex = pageLinks.findIndex(link => link.active);
    const activePage = activeIndex + 1;
    const totalPages = pageLinks.length;

    let visiblePages = [];

    // Selalu tampilkan halaman pertama
    visiblePages.push(pageLinks[0]);

    // Logika untuk menampilkan halaman di sekitar halaman aktif
    if (activePage <= 4) {
        // Jika di awal: 1 2 3 4 5 ... last
        visiblePages.push(...pageLinks.slice(1, 5));
        if (totalPages > 6) {
            visiblePages.push({ label: '...', url: null, active: false });
        }
        visiblePages.push(pageLinks[totalPages - 1]);
    } else if (activePage >= totalPages - 3) {
        // Jika di akhir: 1 ... n-4 n-3 n-2 n-1 n
        visiblePages.push({ label: '...', url: null, active: false });
        visiblePages.push(...pageLinks.slice(totalPages - 5, totalPages));
    } else {
        // Jika di tengah: 1 ... active-1 active active+1 ... last
        visiblePages.push({ label: '...', url: null, active: false });
        visiblePages.push(pageLinks[activeIndex - 1]);
        visiblePages.push(pageLinks[activeIndex]);
        visiblePages.push(pageLinks[activeIndex + 1]);
        visiblePages.push({ label: '...', url: null, active: false });
        visiblePages.push(pageLinks[totalPages - 1]);
    }

    return [prevLink, ...visiblePages, nextLink];
});
</script>

<template>
    <nav v-if="links && links.length > 3">
        <ul class="pagination justify-content-end mb-0">
            <li
                v-for="(link, key) in optimizedLinks"
                :key="key"
                class="page-item"
                :class="{ active: link.active, disabled: !link.url }"
            >
                <Link
                    v-if="link.url"
                    class="page-link"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    prefetch
                    v-html="link.label"
                />
                <span v-else class="page-link" v-html="link.label" />
            </li>
        </ul>
    </nav>
</template>
