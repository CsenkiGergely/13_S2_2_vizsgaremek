<script setup>
import Header from './components/Header.vue'
import Footer from './components/Footer.vue'
import Home from './views/Home.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Header/>

    <!-- Main Content - Ez fog változni oldalanként -->
    <main class="flex-grow">    <!-- nem kell majd ide -->
      <Home />
    </main>

    <Footer/>
  </div>
</template>

<style scoped>
</style>
