<script setup>
defineProps({ message: String });
</script>

<template>
    <div style="padding: 40px; font-family: sans-serif;">
        <h1>Ping Inertia</h1>
        <p>{{ message }}</p>
    </div>
</template>
