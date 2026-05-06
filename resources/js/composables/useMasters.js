import { reactive } from 'vue';

// Module-level singleton — bertahan selama SPA session (tidak refetch saat pindah halaman)
const state = reactive({
    categories: [],
    merks: [],
    groups: [],
    loaded: false,
    loading: false,
});

let inflight = null;

async function load(force = false) {
    if (state.loaded && !force) return state;
    if (inflight) return inflight;

    state.loading = true;
    inflight = fetch('/barang/masters', { headers: { Accept: 'application/json' } })
        .then((r) => r.json())
        .then((data) => {
            state.categories = data.categories ?? [];
            state.merks = data.merks ?? [];
            state.groups = data.groups ?? [];
            state.loaded = true;
            return state;
        })
        .finally(() => {
            state.loading = false;
            inflight = null;
        });

    return inflight;
}

export function useMasters() {
    return { masters: state, loadMasters: load };
}
