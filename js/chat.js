// Chat de dúvidas da Cursonalta: botão flutuante e janela de conversa. Responde pelas palavras-chave, sem IA paga.
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

  function addMsg(text, who) {
    var d = document.createElement("div");
    d.className = "cx-m " + who;
    d.textContent = text;
    log.appendChild(d);
    log.scrollTop = log.scrollHeight;
    return d;
  }

  // ---- Base de respostas (fatos da plataforma). Não usa IA paga: entende pelas palavras-chave ----
  var BASE = [
    { k: ["oi", "ola", "bom dia", "boa tarde", "boa noite", "e ai", "eai", "tudo bem"], a: "Oi! Pode perguntar sobre preços, pagamento, acesso aos cursos, grupo do Telegram ou a DRT de jornalista." },
    { k: ["obrigad", "valeu", "brigado", "thanks"], a: "De nada! Se tiver mais dúvidas, é só perguntar." },
    { k: ["quanto custa", "preco", "valor", "custa", "quanto e", "quanto fica"], a: "Cada curso avulso custa R$ 14,99 e fica com você até concluir. Também existe o pacote de todos os cursos por R$ 24,99, que dá acesso a todos os cursos por 32 dias." },
    { k: ["pacote", "todos os cursos", "todos cursos", "acesso a todos", "plano", "assinatura", "assinar", "mensal", "1 mes", "um mes"], a: "O pacote de todos os cursos custa R$ 24,99, pagamento único, e dá acesso a todos os cursos da plataforma por 32 dias. Não há cobrança automática: quando o prazo acabar, você compra de novo se quiser continuar." },
    { k: ["order bump", "bump", "adicionar acesso", "marcar a opcao", "caixinha"], a: "Na página do curso, marque a opção \"Adicionar acesso a todos os cursos por 1 mês (+ R$ 10,00)\". Você paga R$ 24,99 no total: o curso e 1 mês de todos os cursos." },
    { k: ["renov", "cancel", "cobranca", "cobra todo mes", "cobrar todo mes", "debito automatico"], a: "Não há renovação automática. O pagamento é único, então não existe cobrança recorrente para cancelar. O acesso segue até o fim do prazo já pago." },
    { k: ["pix", "cartao", "boleto", "forma de pagamento", "formas de pagamento", "como pago", "como pagar", "pagar", "pagamento"], a: "Os pagamentos são feitos pelo Mercado Pago, por Pix ou cartão. Escolha o curso ou o pacote e siga as etapas na tela de pagamento." },
    { k: ["reembolso", "devolver", "devolucao", "estorno", "dinheiro de volta", "cancelar compra"], a: "Não tenho essa informação aqui. Para pedir reembolso ou tirar dúvida sobre uma compra, fale com a equipe pelos canais de contato do site." },
    { k: ["nao apareceu", "nao aparece", "nao liberou", "nao libera", "nao ta liberado", "paguei", "ja paguei", "pagamento aprovado", "acesso nao", "cade meu curso", "meu curso"], a: "Depois que o pagamento é aprovado, o acesso é liberado em instantes. Confira na Minha área, em \"Seus cursos\", e aguarde alguns minutos: a página atualiza sozinha. Se continuar sem acesso, recarregue a página ou fale com a equipe." },
    { k: ["minha area", "area do aluno", "onde vejo", "onde assisto", "onde fica meu curso", "entrar", "login", "logar"], a: "Você acompanha seus cursos, seu plano e seu progresso na Minha área. Para entrar, use o botão \"Entrar\" no topo do site." },
    { k: ["senha", "esqueci", "recuperar", "trocar senha", "mudar senha"], a: "Não tenho uma opção automática para trocar a senha aqui. Fale com a equipe pelos canais de contato do site para ajudar com isso." },
    { k: ["criar conta", "cadastro", "cadastrar", "registrar", "conta gratis", "criar uma conta"], a: "Criar conta é grátis. Use o botão \"Criar conta\" no topo do site e depois escolha o curso ou o pacote." },
    { k: ["telegram", "grupo", "link do grupo", "convite", "expirou", "entrar no grupo"], a: "Alguns cursos têm grupo no Telegram, e o link fica na Minha área, para quem tem acesso. Quem tem o pacote de todos os cursos recebe um convite individual para o grupo geral. Se o convite expirar, peça outro pela Minha área. Quando o prazo do pacote acaba, o aluno é removido do grupo geral na verificação diária." },
    { k: ["drt", "jornalista", "registro profissional", "carteira de jornalista"], a: "A Cursonalta também organiza o requerimento da DRT de jornalista. Você envia os documentos e a equipe cuida da papelada até a carteira. Mais informações em drt.cursonalta.com." },
    { k: ["certificado", "certificacao", "diploma"], a: "Não tenho essa informação aqui. Fale com a equipe pelos canais de contato do site." },
    { k: ["o que e", "que e a cursonalta", "sobre a plataforma", "o que voce faz", "como funciona", "como funciona a plataforma", "para que serve"], a: "A Cursonalta é uma plataforma de cursos online em vídeo. Você cria uma conta grátis, compra um curso ou o pacote de todos os cursos e assiste pela Minha área." },
    { k: ["contato", "falar com", "atendimento", "suporte", "humano", "pessoa"], a: "Para falar com a equipe, use os canais de contato indicados no site." }
  ];

  function norm(t) {
    return String(t || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/[^a-z0-9 ]+/g, " ").replace(/\s+/g, " ").trim();
  }

  function answer(text) {
    var q = " " + norm(text) + " ";
    var best = null, bestScore = 0;
    BASE.forEach(function (item) {
      var score = 0;
      item.k.forEach(function (kw) {
        if (q.indexOf(" " + norm(kw) + " ") !== -1 || q.indexOf(norm(kw)) !== -1 && norm(kw).length > 4) score += norm(kw).split(" ").length + (norm(kw).length > 6 ? 1 : 0);
      });
      if (score > bestScore) { bestScore = score; best = item; }
    });
    if (best && bestScore > 0) return best.a;
    return "Não entendi bem a sua pergunta. Tente perguntar sobre preços, pagamento, acesso aos cursos, grupo do Telegram, pacote de todos os cursos ou a DRT de jornalista. Se preferir, fale com a equipe pelos canais de contato do site.";
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

    setTimeout(function () {
      wait.textContent = answer(text);
      send.disabled = false;
      input.focus();
    }, 350);
  });
})();
