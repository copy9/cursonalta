// Menu mobile
(function () {
  var toggle = document.getElementById("menu-toggle");
  var links = document.getElementById("nav-links");
  if (toggle && links) {
    toggle.addEventListener("click", function () {
      links.classList.toggle("open");
    });
  }
})();

// Ano atual no rodapé, se existir
(function () {
  var el = document.getElementById("ano");
  if (el) el.textContent = new Date().getFullYear();
})();

// Formulários de login e cadastro ainda não estão conectados ao backend
(function () {
  document.querySelectorAll("form.form").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      alert("Login e cadastro serão conectados na próxima etapa.");
    });
  });
})();
