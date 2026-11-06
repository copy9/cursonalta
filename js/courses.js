/*
  Catálogo de cursos da Cursonalta.
  Para adicionar um curso, copie um bloco abaixo e troque os dados.
  Quando a plataforma estiver integrada ao WordPress, esta lista passa a vir do painel.
*/
var STATIC_COURSES = [
  {
    id: "policia-civil-pi-agente",
    title: "Polícia Civil do Piauí: Agente",
    shortDescription: "Preparação para o concurso de Agente da Polícia Civil do Piauí, com aulas em vídeo e material de estudo.",
    description: "Curso preparatório para o cargo de Agente da Polícia Civil do Piauí. Conteúdo organizado por disciplina do edital, com aulas em vídeo e material de apoio.",
    category: "Concursos",
    level: "Todos os níveis",
    instructor: { name: "Equipe Cursonalta", role: "Conteúdo de concursos", initials: "CN" },
    price: 14.99,
    duration: "581h 4min",
    lessons: 0,
    cover: "assets/covers/policia-civil-pi-agente.png",
    gradient: "linear-gradient(135deg, #0b1b4d, #1e3a8a)",
    learn: [
      "Conhecer o conteúdo cobrado no edital",
      "Estudar por disciplina, em ordem de prioridade",
      "Acompanhar o próprio progresso"
    ],
    modules: [
      { title: "Módulo em definição", lessons: 0, time: "a definir" }
    ]
  },
  {
    id: "marketing-digital-do-zero",
    title: "Marketing Digital do Zero",
    shortDescription: "Aprenda a atrair clientes online com estratégias práticas de tráfego, conteúdo e vendas.",
    description: "Um passo a passo completo para quem está começando no marketing digital. Você vai entender funis de venda, redes sociais, anúncios pagos e como medir resultados sem gastar além do necessário.",
    category: "Marketing",
    level: "Iniciante",
    instructor: { name: "Marco Antônio", role: "Estrategista digital", initials: "MA" },
    price: 14.99,
    duration: "12h 30min",
    lessons: 36,
    cover: "assets/covers/marketing-digital-do-zero.png",
    gradient: "linear-gradient(135deg, #f97316, #fbbf24)",
    learn: [
      "Montar um funil de vendas do início ao fim",
      "Criar conteúdo que gera interesse e autoridade",
      "Configurar anúncios com orçamento controlado",
      "Analisar métricas e tomar decisões com dados",
      "Organizar uma rotina de publicação sustentável",
      "Fechar as primeiras vendas online"
    ],
    modules: [
      { title: "Fundamentos do marketing digital", lessons: 6, time: "1h 40min" },
      { title: "Estratégia e funil de vendas", lessons: 8, time: "2h 50min" },
      { title: "Conteúdo e redes sociais", lessons: 10, time: "3h 20min" },
      { title: "Tráfego pago na prática", lessons: 7, time: "2h 30min" },
      { title: "Métricas e otimização", lessons: 5, time: "2h 10min" }
    ]
  },
  {
    id: "excel-para-produtividade",
    title: "Excel para Produtividade",
    shortDescription: "Domine planilhas, fórmulas e painéis para economizar horas de trabalho toda semana.",
    description: "Do básico ao avançado: formatação, fórmulas, tabelas dinâmicas e gráficos. Ideal para quem usa Excel no trabalho e quer ganhar agilidade de verdade.",
    category: "Produtividade",
    level: "Todos os níveis",
    instructor: { name: "Juliana Prado", role: "Analista de dados", initials: "JP" },
    price: 14.99,
    duration: "8h 15min",
    lessons: 28,
    cover: "assets/covers/excel-para-produtividade.png",
    gradient: "linear-gradient(135deg, #1e3a8a, #2748b8)",
    learn: [
      "Usar fórmulas e funções de busca com segurança",
      "Criar tabelas dinâmicas em poucos cliques",
      "Montar gráficos claros e profissionais",
      "Automatizar tarefas repetitivas",
      "Organizar planilhas que outras pessoas entendem",
      "Montar um painel de acompanhamento"
    ],
    modules: [
      { title: "Primeiros passos e formatação", lessons: 5, time: "1h 10min" },
      { title: "Fórmulas essenciais", lessons: 8, time: "2h 20min" },
      { title: "Tabelas dinâmicas", lessons: 6, time: "1h 50min" },
      { title: "Gráficos e painéis", lessons: 9, time: "2h 55min" }
    ]
  },
  {
    id: "design-com-ia",
    title: "Design com Inteligência Artificial",
    shortDescription: "Crie artes, posts e apresentações mais rápido usando ferramentas de IA sem perder o estilo.",
    description: "Aprenda a usar ferramentas de IA generativa para criar materiais visuais com qualidade profissional, desde posts para redes sociais até propostas comerciais.",
    category: "Design",
    level: "Intermediário",
    instructor: { name: "Rafael Lima", role: "Designer e criador de conteúdo", initials: "RL" },
    price: 14.99,
    duration: "10h 00min",
    lessons: 30,
    cover: "assets/covers/design-com-ia.png",
    gradient: "linear-gradient(135deg, #7c3aed, #ec4899)",
    learn: [
      "Escrever prompts que geram imagens úteis",
      "Criar identidade visual coerente com IA",
      "Editar e refinar resultados com ferramentas",
      "Produzir peças para redes sociais em série",
      "Usar IA sem violar direitos autorais",
      "Montar um portfólio com os seus trabalhos"
    ],
    modules: [
      { title: "Introdução às ferramentas", lessons: 5, time: "1h 20min" },
      { title: "Prompts para imagem", lessons: 8, time: "2h 40min" },
      { title: "Identidade visual", lessons: 9, time: "3h 00min" },
      { title: "Produção e portfólio", lessons: 8, time: "3h 00min" }
    ]
  },
  {
    id: "ingles-para-negocios",
    title: "Inglês para Negócios",
    shortDescription: "Vocabulário, e-mails e reuniões em inglês para quem quer crescer na carreira.",
    description: "Um curso focado em situações reais do trabalho: apresentações, negociações, reuniões por vídeo e escrita profissional em inglês.",
    category: "Idiomas",
    level: "Intermediário",
    instructor: { name: "Camila Duarte", role: "Professora de inglês", initials: "CD" },
    price: 14.99,
    duration: "15h 45min",
    lessons: 42,
    cover: "assets/covers/ingles-para-negocios.png",
    gradient: "linear-gradient(135deg, #059669, #34d399)",
    learn: [
      "Conduzir reuniões e apresentações em inglês",
      "Escrever e-mails profissionais com clareza",
      "Negociar prazos e valores com segurança",
      "Ampliar vocabulário específico do seu setor",
      "Entender sotaques e falas rápidas",
      "Praticar com exercícios de escuta e fala"
    ],
    modules: [
      { title: "Comunicação no dia a dia", lessons: 8, time: "2h 50min" },
      { title: "E-mails e documentos", lessons: 9, time: "3h 10min" },
      { title: "Reuniões e apresentações", lessons: 13, time: "5h 00min" },
      { title: "Negociação", lessons: 12, time: "4h 45min" }
    ]
  },
  {
    id: "financas-pessoais",
    title: "Finanças Pessoais na Prática",
    shortDescription: "Organize seu orçamento, saia das dívidas e comece a investir com segurança.",
    description: "Um método simples para controlar gastos, criar uma reserva de emergência e entender os principais tipos de investimento, sem linguagem complicada.",
    category: "Finanças",
    level: "Iniciante",
    instructor: { name: "Paulo Henrique", role: "Planejador financeiro", initials: "PH" },
    price: 14.99,
    duration: "6h 40min",
    lessons: 22,
    cover: "assets/covers/financas-pessoais.png",
    gradient: "linear-gradient(135deg, #0ea5e9, #1e3a8a)",
    learn: [
      "Montar um orçamento mensal realista",
      "Sair do cartão rotativo com um plano",
      "Criar uma reserva de emergência",
      "Entender renda fixa e renda variável",
      "Definir metas financeiras de curto e longo prazo",
      "Evitar golpes e decisões por impulso"
    ],
    modules: [
      { title: "Diagnóstico financeiro", lessons: 5, time: "1h 20min" },
      { title: "Orçamento e dívidas", lessons: 7, time: "2h 10min" },
      { title: "Reserva e metas", lessons: 4, time: "1h 30min" },
      { title: "Primeiros investimentos", lessons: 6, time: "1h 40min" }
    ]
  },
  {
    id: "programacao-web-iniciante",
    title: "Programação Web para Iniciantes",
    shortDescription: "Crie sites completos com HTML, CSS e JavaScript a partir do zero.",
    description: "Você sai do zero e constrói páginas responsivas, formulários e pequenas aplicações interativas. Projetos práticos em cada módulo.",
    category: "Tecnologia",
    level: "Iniciante",
    instructor: { name: "Lucas Ferreira", role: "Desenvolvedor front-end", initials: "LF" },
    price: 14.99,
    duration: "20h 10min",
    lessons: 54,
    cover: "assets/covers/programacao-web-iniciante.png",
    gradient: "linear-gradient(135deg, #0b1b4d, #475569)",
    learn: [
      "Estruturar páginas com HTML semântico",
      "Estilizar layouts responsivos com CSS",
      "Programar interações com JavaScript",
      "Publicar um site na internet",
      "Usar Git para versionar seus projetos",
      "Construir um portfólio com três projetos"
    ],
    modules: [
      { title: "HTML e estrutura", lessons: 12, time: "4h 00min" },
      { title: "CSS e layouts", lessons: 16, time: "6h 10min" },
      { title: "JavaScript essencial", lessons: 18, time: "7h 00min" },
      { title: "Projetos e publicação", lessons: 8, time: "3h 00min" }
    ]
  }
];


// Se o admin tiver salvo a lista pelo painel, ela substitui a lista padrão.
window.COURSES = STATIC_COURSES;
(function () {
  try {
    var x = new XMLHttpRequest();
    // Parâmetro único evita que o navegador use uma lista antiga
    x.open("GET", "api/courses.php?t=" + Date.now(), false);
    x.send(null);
    if (x.status === 200) {
      var j = JSON.parse(x.responseText);
      if (j && j.courses && j.courses.length) window.COURSES = j.courses;
    }
  } catch (e) { /* sem conexão: usa a lista padrão */ }
})();

window.ALL_ACCESS_PRICE = 24.99;

window.formatBRL = function (value) {
  return value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
};

window.courseCardHTML = function (c) {
  return `
    <article class="course-card">
      <a href="curso.html?id=${c.id}" class="thumb" style="background:${c.gradient}" aria-label="Ver curso ${c.title}">
        ${c.cover ? `<img src="${c.cover}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">` : ""}
        <span class="tag">${c.category}</span>
        <span class="level">${c.level}</span>
      </a>
      <div class="course-body">
        <h3><a href="curso.html?id=${c.id}">${c.title}</a></h3>
        <p>${c.shortDescription}</p>
        <div class="meta"><span>${c.lessons} aulas</span><span>${c.duration}</span></div>
        <div class="course-foot">
          <div class="price">${formatBRL(c.price)} <small>/mês</small></div>
          <a class="btn btn-primary btn-sm" href="curso.html?id=${c.id}">Ver curso</a>
        </div>
      </div>
    </article>`;
};
