<script>
 const modal = document.getElementById("myModal");
    const openBtn = document.getElementById("openBtn");
    const closeBtn = document.getElementById("closeBtn");

    openBtn.addEventListener("click", () => {
      modal.style.display = "flex"; // megjelenítés
    });

    closeBtn.addEventListener("click", () => {
      modal.style.display = "none"; // elrejtés
    });

    // Kattintás a háttérre és bezárja az ablakot
    window.addEventListener("click", (e) => {
      if(e.target === modal){
        modal.style.display = "none";
      }
    });
</script>
<template>
<button id="openBtn">Nyisd meg az ablakot</button>

  <!-- Modális ablak -->
  <div id="myModal" class="modal">
    <div class="modal-content">
      <p>Ez a felugró ablak tartalma!</p>
      <button class="close-btn" id="closeBtn">Bezárás</button>
    </div>
  </div>
</template>
<style>
/* A modális háttér */
    .modal {
      display: none; /* Alapból rejtett */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5); /* Félátlátszó fekete */
      justify-content: center;
      align-items: center;
    }

    /* A modális tartalom */
    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      width: 300px;
    }

    .close-btn {
      margin-top: 10px;
      padding: 5px 10px;
    }
</style>