/*
  Pop-up do serviço de DRT (assessoria). Aparece uma vez por sessão,
  depois de alguns segundos, e pode ser fechado com o botão, o fundo ou a tecla Esc.
*/
(function () {
  var KEY = "drt-popup-seen";
  var WA = "https://wa.me/3530830124771?text=Quero%20tirar%20minha%20DRT";
  var PAGE = "https://drt.cursonalta.com/";

  function seen() {
    try { return sessionStorage.getItem(KEY) === "1"; } catch (e) { return false; }
  }
  function markSeen() {
    try { sessionStorage.setItem(KEY, "1"); } catch (e) {}
  }

  function build() {
    var wrap = document.createElement("div");
    wrap.className = "pop-overlay";
    wrap.innerHTML =
      '<div class="pop" role="dialog" aria-modal="true" aria-labelledby="pop-title">' +
        '<button type="button" class="pop-close" aria-label="Fechar">&times;</button>' +
        '<span class="pop-kicker">Registro profissional</span>' +
        '<h2 id="pop-title">Precisa tirar sua DRT de jornalista?</h2>' +
        '<p class="pop-lead">Você já tem o trabalho e a formação. O que falta é o registro que comprova a sua profissão. Organizamos o requerimento inteiro, para você não perder tempo com a papelada.</p>' +
        '<ul class="pop-steps">' +
          '<li><span>1</span>Você envia os documentos</li>' +
          '<li><span>2</span>Organizamos o requerimento</li>' +
          '<li><span>3</span>Acompanhamos até a carteira</li>' +
        '</ul>' +
        '<p class="pop-contact">Atendimento direto com o jornalista responsável pelo processo, pelo WhatsApp.</p>' +
        '<div class="pop-actions">' +
          '<a class="btn btn-primary" href="' + WA + '" target="_blank" rel="noopener">Quero tirar minha DRT</a>' +
          '<a class="btn btn-ghost-dark" href="' + PAGE + '">Ver como funciona</a>' +
        '</div>' +
        '<p class="pop-note">Serviço de organização e protocolo do requerimento. A emissão depende de análise do órgão competente, e não há garantia de aprovação ou de prazo.</p>' +
      '</div>';
    document.body.appendChild(wrap);
    return wrap;
  }

  function open() {
    if (seen()) return;
    markSeen();
    var wrap = build();
    requestAnimationFrame(function () { wrap.classList.add("open"); });

    function close() {
      wrap.classList.remove("open");
      document.removeEventListener("keydown", onKey);
      setTimeout(function () { wrap.remove(); }, 250);
    }
    function onKey(e) { if (e.key === "Escape") close(); }

    wrap.querySelector(".pop-close").addEventListener("click", close);
    wrap.addEventListener("click", function (e) { if (e.target === wrap) close(); });
    document.addEventListener("keydown", onKey);
    wrap.querySelector(".pop-close").focus();
  }

  // Atalho fixo: continua disponível mesmo se o pop-up for fechado
  var float = document.createElement("a");
  float.className = "drt-float";
  float.href = PAGE;
  float.textContent = "Tirar minha DRT";
  document.body.appendChild(float);

  setTimeout(open, 4000);
})();
