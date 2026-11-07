// Chat de dúvidas da Cursonalta: botão flutuante e janela de conversa (fala com api/chat.php)
(function () {
  if (window.__chatLoaded) return;
  window.__chatLoaded = true;

  var css = [
    ".cx-btn{position:fixed;right:18px;bottom:18px;z-index:9998;border:0;border-radius:999px;background:#d9480f;color:#fff;font:700 .95rem Inter,system-ui,sans-serif;padding:14px 18px;box-shadow:0 8px 24px rgba(0,0,0,.2);cursor:pointer}",
    ".cx-btn:focus-visible{outline:3px solid #141414;outline-offset:3px}",
    ".cx-box{position:fixed;right:18px;bottom:76px;z-index:9999;width:min(360px,calc(100vw - 36px));height:min(480px,calc(100vh - 120px));background:#fff;border-radius:18px;box-shadow:0 18px 48px rgba(0,0,0,.25);display:none;flex-direction:column;overflow:hidden;font:400 .95rem Inter,system-ui,sans-serif;color:#141414}",
    ".cx-box.open{display:flex}",
    ".cx-head{background:#141414;color:#fff;padding:14px 16px;display:flex;justify-content:space-between;align-items:center}",
    ".cx-head strong{font-size:1rem}",
    ".cx-x{background:transparent;border:0;color:#fff;font-size:1.3rem;cursor:pointer;line-height:1}",
    ".cx-log{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:#f6f5f2}",
    ".cx-m{max-width:85%;padding:10px 13px;border-radius:14px;line-height:1.45;white-space:pre-wrap;word-wrap:break-word}",
    ".cx-m.u{align-self:flex-end;background:#d9480f;color:#fff;border-bottom-right-radius:4px}",
    ".cx-m.b{align-self:flex-start;background:#fff;border:1px solid #e6e4df;border-bottom-left-radius:4px}",
    ".cx-form{display:flex;gap:8px;padding:10px;border-top:1px solid #e6e4df;background:#fff}",
    ".cx-form input{flex:1;border:1px solid #d6d3cc;border-radius:999px;padding:10px 14px;font:inherit;min-width:0}",
    ".cx-form button{border:0;border-radius:999px;background:#141414;color:#fff;padding:10px 16px;font:700 .9rem Inter,system-ui,sans-serif;cursor:pointer}",
    ".cx-form button:disabled{opacity:.5;cursor:default}",
    "@media (max-width:520px){.cx-box{right:10px;left:10px;width:auto;bottom:72px}.cx-btn{right:10px;bottom:10px}}"
  ].join("");

  var style = document.createElement("style");
  style.textContent = css;
  document.head.appendChild(style);

  var btn = document.createElement("button");
  btn.type = "button";
  btn.className = "cx-btn";
  btn.textContent = "Tirar dúvidas";
  btn.setAttribute("aria-expanded", "false");

  var box = document.createElement("div");
  box.className = "cx-box";
  box.setAttribute("role", "dialog");
  box.setAttribute("aria-label", "Chat de dúvidas");
  box.innerHTML =
    '<div class="cx-head"><strong>Dúvidas sobre a Cursonalta</strong><button type="button" class="cx-x" aria-label="Fechar">×</button></div>' +
    '<div class="cx-log" id="cx-log"></div>' +
    '<form class="cx-form" id="cx-form"><input id="cx-in" type="text" maxlength="600" placeholder="Digite sua dúvida" autocomplete="off"><button type="submit" id="cx-send">Enviar</button></form>';

  document.body.appendChild(btn);
  document.body.appendChild(box);

  var log = box.querySelector("#cx-log");
  var form = box.querySelector("#cx-form");
  var input = box.querySelector("#cx-in");
  var send = box.querySelector("#cx-send");
  var history = [];

  function addMsg(text, who) {
    var d = document.createElement("div");
    d.className = "cx-m " + who;
    d.textContent = text;
    log.appendChild(d);
    log.scrollTop = log.scrollHeight;
    return d;
  }

  function open(on) {
    box.classList.toggle("open", on);
    btn.setAttribute("aria-expanded", on ? "true" : "false");
    btn.style.display = on && window.innerWidth <= 520 ? "none" : "";
    if (on) {
      if (!log.children.length) addMsg("Oi! Pode perguntar sobre cursos, pagamento, acesso, grupo do Telegram ou a DRT de jornalista.", "b");
      input.focus();
    }
  }

  btn.addEventListener("click", function () { open(!box.classList.contains("open")); });
  box.querySelector(".cx-x").addEventListener("click", function () { open(false); });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var text = input.value.trim();
    if (!text) return;
    input.value = "";
    addMsg(text, "u");
    send.disabled = true;
    var wait = addMsg("Pensando...", "b");

    fetch("api/chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ message: text, history: history.slice(-6) })
    }).then(function (res) {
      return res.json().catch(function () { return { ok: false }; });
    }).then(function (r) {
      var reply = r && r.ok ? r.reply : ((r && r.error) || "Não consegui responder agora. Tente de novo em instantes.");
      wait.textContent = reply;
      if (r && r.ok) { history.push({ role: "user", text: text }); history.push({ role: "assistant", text: reply }); }
    }).catch(function () {
      wait.textContent = "Sem conexão com o servidor. Tente de novo.";
    }).finally(function () {
      send.disabled = false;
      input.focus();
    });
  });
})();
