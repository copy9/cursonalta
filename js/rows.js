/*
  Fileiras de cursos: setas para avançar/voltar e arraste com o mouse.
  Funciona com qualquer .row > .row-track, inclusive as criadas depois pelo JavaScript.
*/
(function () {
  function enhance(row) {
    if (row.dataset.enhanced) return;
    var track = row.querySelector(".row-track");
    if (!track) return;
    row.dataset.enhanced = "1";

    var nav = document.createElement("div");
    nav.className = "row-nav";
    nav.innerHTML =
      '<button type="button" class="row-btn" data-dir="-1" aria-label="Ver cursos anteriores">&#8249;</button>' +
      '<button type="button" class="row-btn" data-dir="1" aria-label="Ver próximos cursos">&#8250;</button>';
    row.insertBefore(nav, track);

    var prev = nav.children[0];
    var next = nav.children[1];

    nav.addEventListener("click", function (e) {
      var btn = e.target.closest(".row-btn");
      if (!btn) return;
      track.scrollBy({ left: Number(btn.dataset.dir) * track.clientWidth * 0.8, behavior: "smooth" });
    });

    // Arraste com o mouse (no celular o próprio dedo já rola)
    var down = false, moved = false, startX = 0, startLeft = 0;
    track.addEventListener("mousedown", function (e) {
      down = true; moved = false;
      startX = e.pageX; startLeft = track.scrollLeft;
      track.classList.add("dragging");
    });
    window.addEventListener("mousemove", function (e) {
      if (!down) return;
      var dx = e.pageX - startX;
      if (Math.abs(dx) > 4) moved = true;
      track.scrollLeft = startLeft - dx;
    });
    window.addEventListener("mouseup", function () {
      if (!down) return;
      down = false;
      track.classList.remove("dragging");
    });
    // Evita abrir o curso ao soltar depois de arrastar
    track.addEventListener("click", function (e) {
      if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
    }, true);
    track.addEventListener("dragstart", function (e) { e.preventDefault(); });

    function update() {
      var max = track.scrollWidth - track.clientWidth - 2;
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = track.scrollLeft >= max;
    }
    track.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update);
    update();
  }

  function scan() {
    document.querySelectorAll(".row").forEach(enhance);
  }

  scan();
  new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
})();
