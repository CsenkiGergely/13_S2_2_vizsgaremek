<script setup>
import { ref, computed } from 'vue'

const today = new Date().toISOString().split('T')[0]

const searchForm = ref({
  location: '',
  checkIn: '',
  checkOut: '',
  adults: 0,
  children: 0
})

const minCheckOut = computed(() => {
  return searchForm.value.checkIn || today
})

const incrementAdults = () => {
  if (searchForm.value.adults < 10) searchForm.value.adults++
}

const decrementAdults = () => {
  if (searchForm.value.adults > 1) searchForm.value.adults--
}

const incrementChildren = () => {
  if (searchForm.value.children < 10) searchForm.value.children++
}

const decrementChildren = () => {
  if (searchForm.value.children > 0) searchForm.value.children--
}

const handleSearch = () => {
  console.log('Keresés:', searchForm.value)
  // TODO: Keresési logika implementálása
}
</script>

<template>

</template>

<style scoped>

</style>