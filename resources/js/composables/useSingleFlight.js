import { ref } from 'vue';

/**
 * Cegah aksi (mis. delete, toggle) fire lebih dari 1× saat masih in-flight.
 *
 * Pakai key unik (mis. item.id) supaya beberapa row bisa diaksi paralel,
 * tapi 1 row tidak bisa diklik 2× sebelum response pertama selesai.
 *
 *   const { busy, run } = useSingleFlight();
 *
 *   function deleteItem(item) {
 *       run(item.id, (done) => router.delete(`/x/${item.id}`, { onFinish: done }));
 *   }
 *
 *   // Di template:
 *   <button :disabled="busy(item.id)" @click="deleteItem(item)">Delete</button>
 */
export function useSingleFlight() {
    const inFlight = ref(new Set());

    function busy(key = '__global') {
        return inFlight.value.has(key);
    }

    function run(key, action) {
        // Single-arg overload: run(action) → pakai __global
        if (typeof key === 'function') {
            action = key;
            key = '__global';
        }
        if (inFlight.value.has(key)) return false;

        const next = new Set(inFlight.value);
        next.add(key);
        inFlight.value = next;

        const done = () => {
            const after = new Set(inFlight.value);
            after.delete(key);
            inFlight.value = after;
        };

        try {
            action(done);
        } catch (e) {
            done();
            throw e;
        }
        return true;
    }

    return { busy, run };
}
