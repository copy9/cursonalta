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

  // Se o aluno veio de uma compra (?next=api/checkout.php?...), volta para ela depois de entrar
  function nextPage() {
    try {
      var n = new URLSearchParams(window.location.search).get("next") || "";
      if (/^api\/checkout\.php\?plano=(curso|todos)(&curso=[A-Za-z0-9_-]+)?$/.test(n)) return n;
    } catch (e) { /* sem leitura da URL: segue o fluxo normal */ }
    return null;
  }
  function afterAuth(r) {
    var n = nextPage();
    if (n) return n;
    return r.user.role === "admin" ? "admin.html" : "minha-area.html";
  }
  // Mantém o ?next nos links entre entrar e criar conta
  (function keepNext() {
    var n = nextPage();
    if (!n) return;
    document.querySelectorAll('a[href="entrar.html"], a[href="cadastro.html"]').forEach(function (a) {
      a.href = a.getAttribute("href") + "?next=" + encodeURIComponent(n);
    });
  })();

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
        if (r.ok) { window.location.href = afterAuth(r); return; }
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
        if (r.ok) { window.location.href = afterAuth(r); return; }
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
        renderArea(r.user);
      })
      .catch(function () { window.location.replace("entrar.html"); });

    // Ajusta a área ao plano: um curso (escolha única) ou todos os cursos
    function renderArea(user) {
      var planName = document.getElementById("plan-name");
      var planDesc = document.getElementById("plan-desc");
      var planChecks = document.getElementById("plan-checks");
      var statN = document.getElementById("stat-count");
      var statL = document.getElementById("stat-label");

      if (user.plan === "none") {
        planName.textContent = "Sem plano ativo";
        planDesc.textContent = "Seu acesso venceu ou foi cancelado. Assine para continuar.";
        planChecks.innerHTML = "<li>Escolha um curso ou todos os cursos</li>";
        statN.textContent = "0";
        statL.textContent = "cursos no seu plano";
        document.getElementById("course-slot").hidden = true;
        document.getElementById("picker-slot").hidden = true;
        document.getElementById("all-slot").hidden = true;
        return;
      }

      if (user.plan === "todos") {
        planName.textContent = "Todos os cursos";
        planDesc.textContent = formatBRL(window.ALL_ACCESS_PRICE) + " por mês, com renovação automática.";
        planChecks.innerHTML = "<li>Acesso a todos os cursos da plataforma</li><li>Cancele a renovação quando quiser</li>";
        statN.textContent = String(window.COURSES.length);
        statL.textContent = "cursos no seu plano";
        document.getElementById("course-slot").hidden = true;
        document.getElementById("picker-slot").hidden = true;
        renderAll();
        return;
      }

      planName.textContent = "Cursos avulsos";
      planDesc.textContent = formatBRL(14.99) + " por curso, pagamento único. Cada curso fica seu até você concluir.";
      planChecks.innerHTML = "<li>Compre quantos cursos quiser</li><li>Acesso até concluir cada curso</li>";
      statN.textContent = String((user.courses || []).length);
      statL.textContent = "cursos no seu plano";
      document.getElementById("all-slot").hidden = true;
      renderOwned(user.courses || []);
    }

    // Plano de todos os cursos: catálogo completo, sem escolha
    function renderAll() {
      document.getElementById("all-slot").hidden = false;
      document.getElementById("all-grid").innerHTML = window.COURSES.map(function (c) {
        return '<a class="ma-course" href="curso.html?id=' + c.id + '">' +
                 '<div class="ma-course-img">' + (c.cover ? '<img src="' + c.cover + '" alt="" loading="lazy">' : "") + "</div>" +
                 '<div class="ma-course-body">' +
                   '<span class="ma-course-cat">' + c.category + "</span>" +
                   "<strong>" + c.title + "</strong>" +
                   '<div class="ma-course-foot"><span>' + c.lessons + " aulas</span><b>Abrir curso</b></div>" +
                 "</div>" +
               "</a>";
      }).join("");
    }

    // Mostra o curso escolhido, ou a tela para escolher um (plano de um curso)
    function renderCourse(courseId) {
      var slot = document.getElementById("course-slot");
      var picker = document.getElementById("picker-slot");
      var course = null;
      window.COURSES.forEach(function (c) { if (c.id === courseId) course = c; });
      // Curso escolhido que saiu da lista do site: avisa, sem liberar nova escolha
      if (!course && courseId) {
        picker.hidden = true;
        slot.hidden = false;
        slot.innerHTML = '<div class="ma-card"><span class="ma-tag">Seu curso</span>' +
          '<strong style="display:block;font-size:1.1rem">Este curso não está mais disponível.</strong>' +
          '<p style="color:var(--muted);margin:8px 0 0">Fale com a gente pelas dúvidas para resolver o seu acesso.</p>' +
          '<a href="index.html#perguntas" style="color:var(--orange);font-weight:600;display:inline-block;margin-top:12px">Ver dúvidas</a></div>';
        return;
      }
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
              '<button type="button" class="btn btn-ghost" id="tg-btn" data-course="' + course.id + '">Entrar no grupo do Telegram</button>' +
            "</div>" +
          "</div>" +
        "</article>";
    }

    // Lista todos os cursos avulsos que o aluno comprou
    function renderOwned(ids) {
      var slot = document.getElementById("course-slot");
      var picker = document.getElementById("picker-slot");
      picker.hidden = true;
      slot.hidden = false;
      var owned = window.COURSES.filter(function (c) { return ids.indexOf(c.id) !== -1; });
      if (!owned.length) {
        slot.innerHTML = '<div class="ma-card"><span class="ma-tag">Seus cursos</span>' +
          '<strong style="display:block;font-size:1.1rem">Você ainda não comprou nenhum curso.</strong>' +
          '<a href="cursos.html" style="color:var(--orange);font-weight:600;display:inline-block;margin-top:12px">Ver os cursos</a></div>';
        return;
      }
      slot.innerHTML = owned.map(function (course) {
        return '<article class="ma-continue">' +
          '<img src="' + course.cover + '" alt="">' +
          '<div class="ma-continue-info">' +
            '<span class="ma-tag">Seu curso</span>' +
            "<h2>" + course.title + "</h2>" +
            "<p>" + course.shortDescription + "</p>" +
            '<div class="ma-bar"><span style="width:0%"></span></div>' +
            '<div class="ma-meta">0% concluído</div>' +
            '<div class="ma-actions">' +
              '<a class="btn btn-primary" href="curso.html?id=' + course.id + '">Começar o curso</a>' +
              '<button type="button" class="btn btn-ghost tg-btn" data-course="' + course.id + '">Entrar no grupo do Telegram</button>' +
            "</div>" +
          "</div>" +
        "</article>";
      }).join("");
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
                   '<div class="ma-course-foot"><span>' + formatBRL(c.price) + " · acesso até concluir</span><b>Escolher</b></div>" +
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

    // Grupo do Telegram: o link é pedido só no clique, pelo aluno logado com acesso
    document.addEventListener("click", function (e) {
      var b = e.target.closest ? e.target.closest(".tg-btn") : null;
      if (!b) return;
      var msg = document.getElementById("pick-msg");
      var win = window.open("", "_blank");
      fetch("api/group.php?course=" + encodeURIComponent(b.getAttribute("data-course")), { credentials: "same-origin" })
        .then(function (res) { return res.json(); })
        .then(function (r) {
          if (r.ok && r.url) { if (win) win.location.href = r.url; return; }
          if (win) win.close();
          window.alert(r.error || "Não foi possível abrir o grupo.");
        })
        .catch(function () {
          if (win) win.close();
          window.alert("Sem conexão com o servidor. Tente de novo.");
        });
    });

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
