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
      })
      .catch(function () { window.location.replace("entrar.html"); });

    var out = document.getElementById("logout-btn");
    if (out) {
      out.addEventListener("click", function () {
        fetch(API + "?action=logout", { credentials: "same-origin" })
          .finally(function () { window.location.href = "index.html"; });
      });
    }
  }
})();
