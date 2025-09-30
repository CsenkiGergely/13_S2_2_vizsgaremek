<template>
  <div class="container">
    <div class="start w-100">
      <h1 class="text-center pt-2 pt-lg-4">Á.L.B. Ingatlanügynöség</h1>
      <div class="row">
        <div class="col-12 col-sm-6 text-center">
          <router-link class="btn btn-primary" to="/offers"
            >Nézze meg kínálatunkat!</router-link
          >
        </div>
        <div class="col-12 col-sm-6 text-center">
          <router-link class="btn btn-primary" to="/newad"
            >Hirdessen nálunk!</router-link
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Home",
};
</script>