<script setup>
import { useRoute } from 'vue-router'
import Header from './components/Header.vue'
import Footer from './components/Footer.vue'

const route = useRoute()
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <template v-if="route.meta?.skipLayout">
      <router-view />
    </template>

    <template v-else>
      <Header/>
      <main class="grow">
        <router-view />
      </main>
      <Footer/>
    </template>
  </div>
</template>

<style scoped>
</style>
