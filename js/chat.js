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

  // ---- Entendimento: sinônimos, variações e erros de digitação leves. Tudo local, sem custo ----
  // Cada grupo: a palavra "canônica" (primeira) e tudo o que significa a mesma coisa
  var GROUPS = {
    curso: ["curso", "aula", "video", "conteudo", "materia", "treinamento", "formacao", "trilha"],
    avulso: ["avulso", "unitario", "unitaria", "individual", "separado", "solto", "unico", "unica", "1curso", "umcurso", "comprar um", "um so"],
    pacote: ["pacote", "todos", "tudo", "geral", "completo", "ilimitado", "assinatura", "assinar", "plano", "planos", "mensal", "mensalidade", "combo", "bundle"],
    preco: ["preco", "precos", "valor", "valores", "custa", "custo", "custam", "cobram", "cobra", "barato", "caro", "reais", "rs", "price", "taxa", "tabela"],
    pagar: ["pagar", "pagamento", "pagamentos", "pago", "paguei", "pagou", "pagou", "comprar", "compra", "compro", "comprei", "finalizar", "checkout", "cobranca", "cobrou", "cobrado", "debitado", "dinheiro"],
    forma: ["pix", "cartao", "cartoes", "credito", "debito", "boleto", "forma", "formas", "meio", "meios", "opcao", "opcoes", "parcelar", "parcela", "parcelado", "parcelas", "vezes", "mercadopago", "mp"],
    acesso: ["acesso", "acessar", "acessei", "acessou", "liberado", "liberou", "libera", "liberar", "liberacao", "assistir", "abrir", "ver", "vejo", "entrar", "entro", "assisto", "vizualizar", "visualizar"],
    falha: ["nao", "sumiu", "sumi", "cade", "bloqueado", "bloqueou", "travou", "erro", "errado", "problema", "bug", "deu", "falhou", "falha", "demora", "demorou", "ainda"],
    area: ["area", "painel", "dashboard", "perfil", "minha area", "meus cursos", "seus cursos", "seu plano", "meu plano"],
    renovar: ["renovar", "renovacao", "renova", "renovo", "cancelar", "cancela", "cancelamento", "desmarcar", "recorrente", "recorrencia", "automatica", "automatico", "debito automatico", "assinatura automatica", "parar", "todo mes", "todo mês", "mensalmente", "cobra todo mes", "cobranca mensal"],
    reembolso: ["reembolso", "reembolsar", "reembolsa", "estorno", "estornar", "devolucao", "devolver", "devolve", "arrependi", "arrependimento", "dinheiro de volta", "voltar o dinheiro", "garantia"],
    grupo: ["grupo", "telegram", "tele", "canal", "convite", "convites", "link", "comunidade", "turma", "sala"],
    senha: ["senha", "senhas", "esqueci", "esqueceu", "esquecida", "recuperar", "recuperacao", "redefinir", "trocar", "mudar", "alterar", "reset", "login", "logar"],
    criar: ["criar", "cadastro", "cadastrar", "cadastre", "registrar", "registro", "inscrever", "inscricao", "novo", "nova"],
    drt: ["drt", "jornalista", "jornalismo", "jornalistas", "registro profissional", "carteira", "requerimento", "papelada"],
    certificado: ["certificado", "certificados", "certificacao", "diploma", "certificate", "comprovante", "atestado"],
    oi: ["oi", "ola", "eai", "opa", "oie", "hey", "hello", "bom dia", "boa tarde", "boa noite", "fala ai", "salve", "tudo bem", "tudo bom", "e ai"],
    obrigado: ["obrigado", "obrigada", "valeu", "brigado", "brigada", "thanks", "vlw", "agradeco", "agradecido", "show", "perfeito", "ok"],
    contato: ["contato", "contatar", "falar", "atendente", "atendimento", "suporte", "humano", "pessoa", "equipe", "whatsapp", "email", "telefone", "socorro", "ajuda", "ajudar", "responder"],
    site: ["sobre", "plataforma", "site", "cursonalta", "empresa", "quem", "funciona", "servir", "oque", "significa", "faz", "fazem"],
    celular: ["celular", "mobile", "android", "iphone", "app", "aplicativo", "tablet", "computador", "pc", "notebook", "navegador", "tela"],
    tempo: ["prazo", "dura", "duracao", "validade", "valido", "vencer", "vence", "vencimento", "expira", "expiracao", "acaba", "acabar", "termina", "terminar", "ate quando", "quantos dias", "dias"],
    catalogo: ["quantidade", "quantos", "quantas", "numero", "total", "catalogo", "disponivel", "disponiveis", "lista", "opcoes", "variedade", "tem", "existe", "existem"],
    bump: ["bump", "adicionar", "adiciono", "marcar", "marco", "checkbox", "caixinha", "extra", "junto", "junto com", "combinado", "tambem", "tambem"],
    trocar: ["trocar", "mudar", "upgrade", "migrar", "alterar", "outro", "outra", "mais um"],
    comparar: ["diferenca", "diferente", "comparar", "comparacao", "melhor", "vale", "compensa", "qual", "qual e", "escolher", "escolho"],
    intro: ["como", "funciona", "fazer", "faco", "usar", "uso", "passo", "passos", "tutorial", "explica", "explicar", "explique", "ensina"],
    perder: ["perco", "perder", "perdo", "perde", "perdi", "perca", "perdem", "perderei", "sumir", "some", "sumem", "depois", "e depois", "fim", "expirar", "encerrar", "encerra", "acabou", "acaba", "perdo o acesso"]
  };

  // Palavras que não ajudam a entender a pergunta
  var STOP = { "de": 1, "do": 1, "da": 1, "dos": 1, "das": 1, "o": 1, "a": 1, "os": 1, "as": 1, "um": 1, "uma": 1, "e": 1, "que": 1, "pra": 1, "para": 1, "em": 1, "no": 1, "na": 1, "nos": 1, "nas": 1, "eu": 1, "vc": 1, "voce": 1, "com": 1, "por": 1, "ta": 1, "to": 1, "isso": 1, "esse": 1, "essa": 1, "sou": 1, "tenho": 1, "tem": 1, "ter": 1, "foi": 1, "se": 1, "ao": 1, "ja": 1, "so": 1, "mais": 1 };

  // Respostas (fatos da plataforma). Cada uma liga a um conjunto de grupos de palavras.
  var INTENTS = [
    { g: ["oi"], a: "Oi! Pode perguntar sobre preços, pacote de todos os cursos, pagamento, acesso, grupo do Telegram ou a DRT de jornalista." },
    { g: ["obrigado"], a: "De nada! Se tiver mais dúvidas, é só perguntar." },
    { g: ["avulso"], a: "O curso avulso custa R$ 14,99. Você paga uma vez, e o curso fica com você até concluir. Não tem renovação." },
    { g: ["avulso", "preco"], a: "O curso avulso custa R$ 14,99. Você paga uma vez, e o curso fica com você até concluir. Não tem renovação." },
    { g: ["pacote"], a: "O pacote de todos os cursos custa R$ 24,99, pagamento único. Ele dá acesso a todos os cursos da plataforma por 32 dias. Não há cobrança automática." },
    { g: ["perder", "tempo"], a: "Quando os 32 dias do pacote de todos os cursos acabam, o acesso a todos os cursos é encerrado, e você sai do grupo geral. Os cursos que você comprou avulso continuam com você até concluir. Para voltar a ter todos os cursos, compre o pacote de novo." },
    { g: ["perder"], a: "Quando o prazo do pacote de todos os cursos acaba, o acesso a todos os cursos é encerrado, e você sai do grupo geral. Os cursos que você comprou avulso continuam com você até concluir. Para voltar a ter todos os cursos, compre o pacote de novo." },
    { g: ["bump"], a: "Na página do curso, marque \"Adicionar acesso a todos os cursos por 1 mês (+ R$ 10,00)\". Você paga R$ 24,99 no total: o curso e 1 mês de todos os cursos." },
    { g: ["avulso", "curso", "preco"], a: "O curso avulso custa R$ 14,99. Você paga uma vez, e o curso fica com você até concluir. Não tem renovação." },
    { g: ["pacote", "preco"], a: "O pacote de todos os cursos custa R$ 24,99, pagamento único. Ele dá acesso a todos os cursos da plataforma por 32 dias. Não há cobrança automática: quando o prazo acabar, você compra de novo se quiser continuar." },
    { g: ["pacote", "avulso", "comparar"], a: "Curso avulso: R$ 14,99 por curso, e o curso fica com você até concluir. Pacote de todos os cursos: R$ 24,99 por 32 dias, com acesso a todos os cursos. Se você quer um curso só, o avulso sai mais barato. Se quer vários, o pacote compensa." },
    { g: ["preco"], a: "Preços: curso avulso R$ 14,99 (fica com você até concluir) e pacote de todos os cursos R$ 24,99 (32 dias de acesso a todos os cursos, pagamento único)." },
    { g: ["bump", "avulso", "pacote"], a: "Na página do curso, marque \"Adicionar acesso a todos os cursos por 1 mês (+ R$ 10,00)\". Você paga R$ 24,99 no total: o curso e 1 mês de todos os cursos." },
    { g: ["renovar"], a: "Não há renovação automática. O pagamento é único, então não existe cobrança recorrente para cancelar. O acesso segue até o fim do prazo já pago." },
    { g: ["forma", "pagar"], a: "Os pagamentos são feitos pelo Mercado Pago, por Pix ou cartão. Escolha o curso ou o pacote e siga as etapas na tela de pagamento." },
    { g: ["forma"], a: "Os pagamentos são feitos pelo Mercado Pago, por Pix ou cartão. As opções de parcelamento, se houver, aparecem na tela de pagamento do Mercado Pago." },
    { g: ["pagar"], a: "Para pagar, escolha o curso ou o pacote e siga as etapas na tela de pagamento do Mercado Pago. Pix e cartão são aceitos." },
    { g: ["reembolso"], a: "Não tenho essa informação aqui. Para pedir reembolso ou tirar dúvida sobre uma compra, fale com a equipe pelos canais de contato do site." },
    { g: ["falha", "pagar"], a: "Se o pagamento foi aprovado, o acesso é liberado em instantes. Confira na Minha área, em \"Seus cursos\", e aguarde alguns minutos: a página atualiza sozinha. Se continuar sem acesso, recarregue a página ou fale com a equipe." },
    { g: ["falha", "acesso"], a: "Se o acesso não aparece, confira na Minha área, em \"Seus cursos\". Aguarde alguns minutos e recarregue a página. Se continuar, fale com a equipe pelos canais do site." },
    { g: ["falha", "curso"], a: "Se o curso não aparece, confira na Minha área, em \"Seus cursos\". Depois do pagamento aprovado, ele é liberado em instantes. Se não aparecer, fale com a equipe." },
    { g: ["tempo", "avulso"], a: "O curso avulso fica com você até concluir. Não tem prazo para terminar." },
    { g: ["tempo", "pacote"], a: "O pacote de todos os cursos dá acesso por 32 dias, a partir do pagamento. Depois disso, você compra de novo se quiser continuar." },
    { g: ["tempo"], a: "O curso avulso fica com você até concluir. O pacote de todos os cursos dá 32 dias de acesso." },
    { g: ["area"], a: "Na Minha área você acompanha seus cursos, seu plano e seu progresso. Para entrar, use o botão \"Entrar\" no topo do site." },
    { g: ["acesso", "area"], a: "O acesso aos seus cursos fica na Minha área, em \"Seus cursos\". Para entrar, use o botão \"Entrar\" no topo do site." },
    { g: ["acesso"], a: "Depois que o pagamento é aprovado, o acesso é liberado. Você assiste pela Minha área, em \"Seus cursos\"." },
    { g: ["trocar", "pacote"], a: "Na Minha área, em \"Seu plano\", você pode comprar o pacote de todos os cursos ou outro curso avulso." },
    { g: ["trocar"], a: "Na Minha área, em \"Seu plano\", você pode comprar o pacote de todos os cursos ou outro curso avulso." },
    { g: ["senha"], a: "Não tenho uma opção automática para trocar a senha aqui. Fale com a equipe pelos canais de contato do site para ajudar com isso." },
    { g: ["criar"], a: "Criar conta é grátis. Use o botão \"Criar conta\" no topo do site e depois escolha o curso ou o pacote." },
    { g: ["grupo"], a: "Alguns cursos têm grupo no Telegram, e o link fica na Minha área, para quem tem acesso. Quem tem o pacote de todos os cursos recebe um convite individual para o grupo geral. Se o convite expirar, peça outro pela Minha área. Quando o prazo do pacote acaba, o aluno é removido do grupo geral na verificação diária." },
    { g: ["grupo", "falha"], a: "Se o link do grupo não funciona, peça outro pela Minha área. O convite do pacote é individual e vale por pouco tempo. Se continuar com problema, fale com a equipe." },
    { g: ["drt"], a: "A Cursonalta também organiza o requerimento da DRT de jornalista. Você envia os documentos e a equipe cuida da papelada até a carteira. Mais informações em drt.cursonalta.com." },
    { g: ["certificado"], a: "Não tenho essa informação aqui. Fale com a equipe pelos canais de contato do site." },
    { g: ["celular"], a: "Você pode assistir pelo navegador, no celular ou no computador. O site se adapta à tela." },
    { g: ["site"], a: "A Cursonalta é uma plataforma de cursos online em vídeo. Você cria uma conta grátis, compra um curso avulso ou o pacote de todos os cursos e assiste pela Minha área." },
    { g: ["contato"], a: "Para falar com a equipe, use os canais de contato indicados no site." },
    { g: ["catalogo"], a: "Há vários cursos disponíveis. Veja todos na página Cursos, com busca e filtros por categoria e nível." }
  ];

  function norm(t) {
    return String(t || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/[^a-z0-9 ]+/g, " ").replace(/\s+/g, " ").trim();
  }

  // Distância de edição (para aceitar erros de digitação leves)
  function lev(a, b) {
    var m = a.length, n = b.length, d = [], i, j;
    for (i = 0; i <= m; i++) { d[i] = [i]; }
    for (j = 0; j <= n; j++) { d[0][j] = j; }
    for (i = 1; i <= m; i++) for (j = 1; j <= n; j++) {
      d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
    }
    return d[m][n];
  }

  // Quais grupos a pergunta toca: procura cada termo do grupo como palavra ou expressão
  function groupsIn(text) {
    var hits = {};
    var q = " " + norm(text) + " ";
    var words = norm(text).split(" ").filter(function (w) { return w && !STOP[w]; });
    Object.keys(GROUPS).forEach(function (g) {
      GROUPS[g].forEach(function (term) {
        var t = norm(term);
        if (!t) return;
        if (t.indexOf(" ") !== -1) { if (q.indexOf(" " + t + " ") !== -1) hits[g] = true; return; }
        words.forEach(function (w) {
          if (w === t) hits[g] = true;
          else if (t.length >= 5 && w.length >= 5 && (w.indexOf(t) === 0 || t.indexOf(w) === 0)) hits[g] = true;   // plural/radical
          else if (t.length >= 5 && w.length >= 5 && lev(w, t) <= 1) hits[g] = true;                             // erro de digitação
        });
      });
    });
    return hits;
  }

  // Peso de cada assunto: os específicos valem mais; as palavras genéricas quase não contam
  var STRONG = { renovar: 1, reembolso: 1, grupo: 1, senha: 1, criar: 1, drt: 1, certificado: 1, celular: 1, bump: 1, site: 1 };
  var GENERIC = { intro: 1, comparar: 1, catalogo: 1 };
  function weight(g) { return STRONG[g] ? 2 : (GENERIC[g] ? 0.5 : 1); }

  function answer(text) {
    var hits = groupsIn(text);
    var best = null, bestScore = 0;
    // Primeiro: respostas em que todos os assuntos da resposta aparecem na pergunta
    INTENTS.forEach(function (it) {
      if (!it.g.every(function (g) { return hits[g]; })) return;
      var score = it.g.reduce(function (acc, g) { return acc + weight(g); }, 0) + it.g.length * 0.01;
      if (score > bestScore) { bestScore = score; best = it; }
    });
    if (best) return best.a;
    // Depois: a resposta que mais se aproxima, se a pergunta tiver pelo menos dois assuntos conhecidos
    var partial = null, ps = 0;
    INTENTS.forEach(function (it) {
      var sc = it.g.reduce(function (acc, g) { return acc + (hits[g] ? weight(g) : 0); }, 0);
      if (sc > ps) { ps = sc; partial = it; }
    });
    if (partial && ps >= 2) return partial.a;
    return "Não entendi essa pergunta. Tente algo como: \"quanto custa um curso?\", \"como pago com Pix?\", \"paguei e não liberou\", \"como entro no grupo do Telegram?\", \"tem reembolso?\" ou \"o que é a DRT?\". Se preferir, fale com a equipe pelos canais de contato do site.";
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
