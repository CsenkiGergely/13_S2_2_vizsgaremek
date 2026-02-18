<template>
  <div class="spot-kezeles-container">
    <h1>Kemping helyek kezelése</h1>
    <p>Kemping ID: {{ campingId }}</p>
    <!-- TODO: spot CRUD implementáció -->
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()
const campingId = route.params.id
</script>

<style scoped>
.spot-kezeles-container {
  padding: 2rem;
}
</style>
