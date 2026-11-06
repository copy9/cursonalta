// Liga os formulários de cadastro e login ao api/auth.php
(function () {
  var API = "api/auth.php";

  function post(action, data) {
    return fetch(API + "?action=" + action, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(data)
    }).then(function (res) {
      return res.json().catch(function () { return { ok: false, error: "Resposta inesperada do servidor." }; })
        .then(function (json) { json.status = res.status; return json; });
    });
  }

  function showMsg(el, text) {
    el.textContent = text;
    el.style.display = text ? "block" : "none";
  }

  function setBusy(btn, busy, label) {
    btn.disabled = busy;
    btn.textContent = busy ? "Aguarde..." : label;
  }

  // Cadastro
  var signup = document.getElementById("signup-form");
  if (signup) {
    var msg = document.getElementById("form-msg");
    signup.addEventListener("submit", function (e) {
      e.preventDefault();
      var btn = signup.querySelector("button[type=submit]");
      var label = btn.textContent;
      showMsg(msg, "");
      setBusy(btn, true, label);
      post("register", {
        name: document.getElementById("name").value,
        email: document.getElementById("email").value,
        password: document.getElementById("password").value
      }).then(function (r) {
        if (r.ok) { window.location.href = "minha-area.html"; return; }
        showMsg(msg, r.error || "Não foi possível criar sua conta.");
        setBusy(btn, false, label);
      }).catch(function () {
        showMsg(msg, "Sem conexão com o servidor. Tente novamente.");
        setBusy(btn, false, label);
      });
    });
  }

  // Login
  var login = document.getElementById("login-form");
  if (login) {
    var lmsg = document.getElementById("form-msg");
    login.addEventListener("submit", function (e) {
      e.preventDefault();
      var btn = login.querySelector("button[type=submit]");
      var label = btn.textContent;
      showMsg(lmsg, "");
      setBusy(btn, true, label);
      post("login", {
        email: document.getElementById("email").value,
        password: document.getElementById("password").value
      }).then(function (r) {
        if (r.ok) { window.location.href = "minha-area.html"; return; }
        showMsg(lmsg, r.error || "Não foi possível entrar.");
        setBusy(btn, false, label);
      }).catch(function () {
        showMsg(lmsg, "Sem conexão com o servidor. Tente novamente.");
        setBusy(btn, false, label);
      });
    });
  }

  // Área do aluno: exige sessão e preenche os dados da conta
  var area = document.getElementById("area-account");
  if (area) {
    fetch(API + "?action=me", { credentials: "same-origin" })
      .then(function (res) { return res.json(); })
      .then(function (r) {
        if (!r.ok) { window.location.replace("entrar.html"); return; }
        document.getElementById("area-name").textContent = r.user.name;
        document.getElementById("area-email").textContent = r.user.email;
        document.getElementById("area-hello").textContent = "Olá, " + r.user.name.split(" ")[0];
        area.style.visibility = "visible";
        renderCourse(r.user.course);
      })
      .catch(function () { window.location.replace("entrar.html"); });

    // Mostra o curso escolhido, ou a tela para escolher um (plano de um curso)
    function renderCourse(courseId) {
      var slot = document.getElementById("course-slot");
      var picker = document.getElementById("picker-slot");
      var course = null;
      window.COURSES.forEach(function (c) { if (c.id === courseId) course = c; });
      if (!course) { slot.hidden = true; showPicker(); return; }
      picker.hidden = true;
      slot.hidden = false;
      slot.innerHTML =
        '<article class="ma-continue">' +
          '<img src="' + course.cover + '" alt="">' +
          '<div class="ma-continue-info">' +
            '<span class="ma-tag">Seu curso</span>' +
            "<h2>" + course.title + "</h2>" +
            "<p>" + course.shortDescription + "</p>" +
            '<div class="ma-bar"><span style="width:0%"></span></div>' +
            '<div class="ma-meta">0% concluído</div>' +
            '<div class="ma-actions">' +
              '<a class="btn btn-primary" href="curso.html?id=' + course.id + '">Começar o curso</a>' +
            "</div>" +
          "</div>" +
        "</article>";
    }

    function showPicker() {
      var picker = document.getElementById("picker-slot");
      var grid = document.getElementById("picker-grid");
      picker.hidden = false;
      grid.innerHTML = window.COURSES.map(function (c) {
        return '<button type="button" class="ma-course ma-pick" data-id="' + c.id + '">' +
                 '<div class="ma-course-img">' + (c.cover ? '<img src="' + c.cover + '" alt="" loading="lazy">' : "") + "</div>" +
                 '<div class="ma-course-body">' +
                   '<span class="ma-course-cat">' + c.category + "</span>" +
                   "<strong>" + c.title + "</strong>" +
                   '<div class="ma-course-foot"><span>' + formatBRL(c.price) + "/mês no plano</span><b>Escolher</b></div>" +
                 "</div>" +
               "</button>";
      }).join("");
      grid.querySelectorAll(".ma-pick").forEach(function (btn) {
        btn.addEventListener("click", function () {
          var msg = document.getElementById("pick-msg");
          btn.disabled = true;
          fetch(API + "?action=course", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ course: btn.getAttribute("data-id") })
          }).then(function (res) { return res.json(); })
            .then(function (r) {
              if (r.ok) { renderCourse(r.user.course); return; }
              msg.textContent = r.error || "Não foi possível escolher o curso.";
              btn.disabled = false;
            })
            .catch(function () {
              msg.textContent = "Sem conexão com o servidor. Tente novamente.";
              btn.disabled = false;
            });
        });
      });
    }

    var out = document.getElementById("logout-btn");
    if (out) {
      out.addEventListener("click", function () {
        fetch(API + "?action=logout", { credentials: "same-origin" })
          .finally(function () { window.location.href = "index.html"; });
      });
    }
  }
})();

// Cabeçalho: com sessão ativa, mostra "Minha área" e "Sair" no lugar de "Entrar" e "Criar conta"
(function () {
  var actions = document.querySelector(".nav-actions");
  if (!actions) return;
  var login = actions.querySelector('a[href="entrar.html"]');
  var signup = actions.querySelector('a[href="cadastro.html"]');

  fetch("api/auth.php?action=me", { credentials: "same-origin" })
    .then(function (res) { return res.json(); })
    .then(function (r) {
      if (!r.ok || !login || !signup) return;
      login.textContent = "Minha área";
      login.setAttribute("href", "minha-area.html");
      signup.textContent = "Sair";
      signup.setAttribute("href", "#");
      signup.addEventListener("click", function (e) {
        e.preventDefault();
        fetch("api/auth.php?action=logout", { credentials: "same-origin" })
          .finally(function () { window.location.href = "index.html"; });
      });
    })
    .catch(function () {});
})();
