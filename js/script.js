// ... (todo o código existente no seu js/script.js, incluindo loadTela e carregarTemasBD) ...

// ==========================================
// Inicialização Final (Substituir o bloco antigo)
// ==========================================

// Função robusta de inicialização para garantir a ordem assíncrona
async function initializeApp() {
    // 1. Carrega temas do banco de dados (AGUARDA)
    await carregarTemasBD(); 
    
    // 2. Inicia o estado da aplicação e carrega a primeira tela
    loadTela(telaAtual); 
}

// Inicializa o processo ao carregar a página (Garantir que os elementos HTML existam)
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

// NOTA: Certifique-se que o flatpickr é inicializado DENTRO da função loadTela, 
// apenas quando a tela5 for carregada.

// ==========================================
// Variáveis de Estado e Configuração
// ==========================================
let telaAtual = 1;
const totalTelas = 10;
let temasPorFesta = {}; // Armazena temas do BD
let quantidadesAdicionais = [];
const adicionaisConfig = [
    { nome: "Bolo (2 Recheios)", descricao: "Adicione 10 fatias", valor: 60 },
    { nome: "Docinhos simples", descricao: "1 Porção c/ 10 uni.", valor: 4.50 },
    { nome: "Docinhos gourmet", descricao: "1 Porção c/ 5 uni.", valor: 8 },
    { nome: "Cupcakes", descricao: "1 Porção c/ 5 uni.", valor: 20 },
    { nome: "Mini Donuts", descricao: "1 Porção c/ 5 uni.", valor: 10 },
];
quantidadesAdicionais = Array(adicionaisConfig.length).fill(0);

// Objeto que armazena o estado do pedido (dados da tela)
let pedidoData = {
    // Tela 2: Ocasião
    tipoFesta: 'Aniversário', 
    // Tela 3: Tema
    tema: '',
    temaOutro: false,
    // Tela 4: Homenageado
    nomeHomenageado: '',
    idadeHomenageado: 0,
    // Tela 5: Data
    dataFesta: '',
    // Tela 6: Tamanho
    tamanhoSelecionado: 0, // 0 = Festa na Mesa
    // Tela 7: Combo
    mesaAtivada: true,
    comboSelecionado: null, // 0: Essencial, 1: Fantástico, 2: Inesquecível
    // Tela 8: Adicionais - Controlado por quantidadesAdicionais array
    adicionaisAtivos: true,
    // Tela 9: Dados
    nomeCliente: '',
    telefoneCliente: '',
    formaPagamento: null,
};


// ==========================================
// Funções de Persistência (Salvar/Restaurar)
// ==========================================

function salvarDadosTela(n) {
    switch (n) {
        case 2:
            pedidoData.tipoFesta = document.querySelector('input[name="tipoFesta"]:checked')?.value || null;
            break;
        case 3:
            pedidoData.tema = document.getElementById("pesquisaTema").value.trim();
            pedidoData.temaOutro = document.getElementById("temaOutro").checked;
            if (pedidoData.temaOutro) {
                pedidoData.tema = document.getElementById("novoTema").value.trim();
            }
            break;
        case 4:
            pedidoData.nomeHomenageado = document.getElementById("nomeHomenageado").value.trim();
            pedidoData.idadeHomenageado = parseInt(document.getElementById("idadeHomenageado").value) || 0;
            break;
        case 5:
            pedidoData.dataFesta = document.getElementById("dataFesta").value;
            break;
        case 7:
            // Mesa e Combo são salvos pelas funções toggleMesa e selecionarCombo
            break;
        case 9:
            pedidoData.nomeCliente = document.getElementById("nomeCliente").value.trim();
            pedidoData.telefoneCliente = document.getElementById("telefoneCliente").value.trim();
            pedidoData.formaPagamento = document.querySelector('input[name="formaPagamento"]:checked')?.value || null;
            break;
    }
    // Salva o objeto completo no Local Storage para maior persistência (opcional)
    localStorage.setItem('encantivaPedidoData', JSON.stringify(pedidoData));
}

function restaurarDadosTela(n) {
    // 1. Tenta carregar do Local Storage na inicialização
    if (n === 1 && localStorage.getItem('encantivaPedidoData')) {
        const storedData = JSON.parse(localStorage.getItem('encantivaPedidoData'));
        // Faz o merge dos dados persistidos com o estado inicial
        pedidoData = { ...pedidoData, ...storedData };
    }

    // 2. Restaura campos de input na tela atual
    switch (n) {
        case 2:
            if (pedidoData.tipoFesta) {
                document.querySelector(`input[name="tipoFesta"][value="${pedidoData.tipoFesta}"]`).checked = true;
            }
            break;
        case 3:
            if (pedidoData.temaOutro) {
                document.getElementById("temaOutro").checked = true;
                ativarTemaOutro(); 
                document.getElementById("novoTema").value = pedidoData.tema;
            } else if (pedidoData.tema) {
                document.getElementById("pesquisaTema").value = pedidoData.tema;
            }
            break;
        case 4:
            document.getElementById("nomeHomenageado").value = pedidoData.nomeHomenageado;
            document.getElementById("idadeHomenageado").value = pedidoData.idadeHomenageado || '';
            break;
        case 5:
            document.getElementById("dataFesta").value = pedidoData.dataFesta;
            break;
        case 6: // Tamanho (requer a inicialização dos cards)
            selecionarTamanho(pedidoData.tamanhoSelecionado, false); 
            break;
        case 7: // Combo (requer a inicialização dos cards)
            // Lógica de mesa
            if (pedidoData.mesaAtivada) toggleMesa(false); else toggleMesa(false);
            // Lógica de combo
            if (pedidoData.comboSelecionado !== null) selecionarCombo(pedidoData.comboSelecionado, false);
            break;
        case 9:
            document.getElementById("nomeCliente").value = pedidoData.nomeCliente;
            document.getElementById("telefoneCliente").value = pedidoData.telefoneCliente;
            if (pedidoData.formaPagamento) {
                document.querySelector(`input[name="formaPagamento"][value="${pedidoData.formaPagamento}"]`).checked = true;
            }
            break;
    }
}


// ==========================================
// Navegação e Carregamento Principal
// ==========================================

// 1. Carrega temas do banco de dados (inicial)
async function carregarTemasBD() {
    try {
        // CORREÇÃO: Busca temas do novo endpoint PHP
        const response = await fetch('carregar_temas.php'); 
        const data = await response.json();
        temasPorFesta = data;
    } catch (error) {
        console.error("Erro ao carregar temas do BD:", error);
        exibirErro("Falha ao carregar temas disponíveis. Recarregue a página.");
    }
}

// 2. Função principal de navegação e carregamento
async function loadTela(n, direction = 'next') {
    // 0. Validação ao avançar
    if (direction === 'next' && !validarTelaAtual()) return;

    // 1. Salva dados da tela atual antes de sair
    if (telaAtual > 1) salvarDadosTela(telaAtual);

    telaAtual = n;
    
    // 2. Carrega o HTML da nova tela
    try {
        const urlParams = new URLSearchParams({
            // Passa o tipo de festa para o PHP (usado em tela3.php para filtrar)
            tipo: pedidoData.tipoFesta || 'Aniversário' 
        });

        const response = await fetch(`telas/tela${n}.php?${urlParams}`);
        const html = await response.text();
        document.getElementById('content-container').innerHTML = html;
        atualizarProgresso();
        
        // 3. Restaura dados e inicializa elementos específicos da nova tela
        // Deve ser chamado DEPOIS que o DOM foi atualizado
        restaurarDadosTela(n); 

        // 4. Inicializações específicas (Flatpickr, Adicionais, etc.)
        if (n === 5) { // Tela 5: Data
            flatpickr("#dataFesta", {
                dateFormat: "d/m/Y",  
                minDate: new Date().fp_incr(7),      
                allowInput: true       
            });
        }
        
        if (n === 3) await carregarTemasTela3();
        if (n === 8) renderizarAdicionais();
        if (n === 10) {
            gerarResumo(); // Gera o resumo com os dados atualizados
            atualizarPagamentoResumo();
        }

    } catch (error) {
        console.error("Erro ao carregar tela:", error);
        exibirErro("Erro ao carregar tela do processo.");
    }
}

function proximaTela(n) { loadTela(n, 'next'); }
function voltarTela(n) { loadTela(n, 'prev'); }


// ==========================================
// Funções Auxiliares de Componentes
// ==========================================

// Função para Tela 3 - Carrega temas filtrados
// ... (dentro de js/script.js, na seção de Funções Auxiliares de Componentes)

function mostrarTemas() {
  if (!document.getElementById("temaOutro").checked) {
    document.getElementById("listaTemas").style.display = "block";
  }
}

function filtrarTemas() {
  const termo = document.getElementById("pesquisaTema").value.toLowerCase();
  
  // Pega o tipo de festa atual do estado do pedido
  const tipoSelecionado = pedidoData.tipoFesta;
  const listaDiv = document.getElementById("listaTemas");

  // Limpa e repopula a lista com base no termo de busca e nos temas do BD
  listaDiv.innerHTML = "";
  (temasPorFesta[tipoSelecionado] || []).forEach(tema => {
    if (tema.toLowerCase().includes(termo.toLowerCase())) {
        const label = document.createElement("label");
        label.textContent = tema;
        label.onclick = () => {
            document.getElementById("pesquisaTema").value = tema;
            document.getElementById("listaTemas").style.display = "none";
            salvarDadosTela(3); // Salva o tema escolhido imediatamente
        };
        listaDiv.appendChild(label);
    }
  });

  // Mostra o container, se não estiver oculto
  if (!document.getElementById("temaOutro").checked) {
      document.getElementById("listaTemas").style.display = "block";
  }
}

// Handler global para fechar o dropdown se clicar fora
document.addEventListener("click", e => {
  const container = document.querySelector(".select-busca");
  const listaTemas = document.getElementById("listaTemas");
  if (listaTemas && container && !container.contains(e.target)) {
    listaTemas.style.display = "none";
  }
});

function ativarTemaOutro() {
  const checkbox = document.getElementById("temaOutro");
  const novoTema = document.getElementById("novoTema");
  const pesquisa = document.getElementById("pesquisaTema");
  const listaTemas = document.getElementById("listaTemas");

  if (checkbox.checked) {
    novoTema.style.display = "block";
    pesquisa.disabled = true;
    listaTemas.style.display = "none";
    pesquisa.value = ''; // Limpa o campo de pesquisa padrão
    pedidoData.temaOutro = true; // Atualiza o estado
  } else {
    novoTema.style.display = "none";
    pesquisa.disabled = false;
    novoTema.value = ''; // Limpa o campo de tema novo
    pedidoData.temaOutro = false; // Atualiza o estado
  }
  salvarDadosTela(3); // Garante que o estado é persistido
}

// Adicionar no final da função de carregamento para garantir que a lista seja populada
async function carregarTemasTela3() {
    const tipoSelecionado = pedidoData.tipoFesta;
    const listaDiv = document.getElementById("listaTemas");
    listaDiv.innerHTML = "";

    if (!tipoSelecionado) {
        listaDiv.innerHTML = "<p>Por favor, volte e selecione um tipo de festa.</p>";
        return;
    }
    
    // Garante que os temas foram carregados do BD antes de tentar usar
    await carregarTemasBD(); 

    (temasPorFesta[tipoSelecionado] || []).forEach(tema => {
        const label = document.createElement("label");
        label.textContent = tema;
        label.onclick = () => {
            document.getElementById("pesquisaTema").value = tema;
            document.getElementById("listaTemas").style.display = "none";
            salvarDadosTela(3); 
        };
        listaDiv.appendChild(label);
    });
}

function selecionarTamanho(index, updateState = true) {
    document.querySelectorAll('#tela6 .card-tamanho').forEach((card, i) => {
        card.classList.toggle('selecionado', i === index);
    });
    if (updateState) {
        pedidoData.tamanhoSelecionado = index;
        pedidoData.comboSelecionado = null; // Reseta combo ao mudar tamanho
        // Força a atualização visual dos combos na próxima tela (opcional)
    }
}

function toggleMesa(updateState = true) {
    if (updateState) pedidoData.mesaAtivada = !pedidoData.mesaAtivada;
    
    const switchEl = document.getElementById('switch');
    const label = document.getElementById('mesa-label');

    switchEl.classList.toggle('active', pedidoData.mesaAtivada);
    label.textContent = pedidoData.mesaAtivada ? "Mesa adicionada (+R$10)" : "Adicionar mesa (+R$10)";

    atualizarValorTotal();
}

function selecionarCombo(index, updateState = true) {
    const cards = document.querySelectorAll('.card-combo');
    
    cards.forEach((card, i) => card.classList.remove('selecionado'));
    
    if (pedidoData.comboSelecionado === index && updateState) {
        pedidoData.comboSelecionado = null;
    } else {
        cards[index]?.classList.add('selecionado');
        if (updateState) pedidoData.comboSelecionado = index;
    }
    atualizarValorTotal();
}


// Funções de Adicionais
function alterarQuantidade(index, delta) {
  const maximo = 20; 
  quantidadesAdicionais[index] = Math.min(
    maximo,
    Math.max(0, (quantidadesAdicionais[index] || 0) + delta)
  );
  document.getElementById(`qtd-${index}`).textContent = quantidadesAdicionais[index];

  atualizarValorTotal();
}

function toggleAdicionais() {
    pedidoData.adicionaisAtivos = !pedidoData.adicionaisAtivos;
    // O restante da lógica está em renderizarAdicionais/atualizarValorTotal
    renderizarAdicionais();
    atualizarValorTotal();
}

function renderizarAdicionais() {
  const container = document.getElementById("adicionaisContainer");
  if (!container) return; // Garante que estamos na Tela 8

  container.innerHTML = "";
  container.style.display = pedidoData.adicionaisAtivos ? "block" : "none";

  const switchEl = document.getElementById("switchAdicionais");
  const labelAdd = document.getElementById("adicionais-label");
  switchEl.classList.toggle("active", pedidoData.adicionaisAtivos);
  labelAdd.textContent = pedidoData.adicionaisAtivos ? "Quero adicionais" : "Não quero adicionais";

  if (!pedidoData.adicionaisAtivos) {
    quantidadesAdicionais = Array(adicionaisConfig.length).fill(0);
    atualizarValorTotal();
    return;
  }

  adicionaisConfig.forEach((item, index) => {
    const card = document.createElement("div");
    card.className = "adicional-card";
    card.innerHTML = `
      <div class="info">
        <h4>${item.nome}</h4>
        <p>${item.descricao}</p>
        <span class="preco">R$ ${item.valor.toFixed(2)}</span>
      </div>
      <div class="quantidade">
        <button onclick="alterarQuantidade(${index}, -1)">-</button>
        <span id="qtd-${index}">${quantidadesAdicionais[index] || 0}</span>
        <button onclick="alterarQuantidade(${index}, 1)">+</button>
      </div>
    `;
    container.appendChild(card);
  });

  document.getElementById("totalAdicionais").textContent = `R$ ${atualizarValorTotal().toFixed(2)}`;
}

// ==========================================
// Cálculo de Valores
// ==========================================

function atualizarValorTotal() {
  let total = 0;

  // 1. Valor do combo
  if (pedidoData.comboSelecionado !== null) {
    // É necessário rebuscar o valor do combo, já que não temos o card HTML
    const comboValues = [39.90, 59.90, 109.90]; // Hardcoding valores do combo (essencial, fantástico, inesquecível)
    total += comboValues[pedidoData.comboSelecionado] || 0;
  }

  // 2. Adicionar mesa
  if (pedidoData.mesaAtivada) total += 10;

  // 3. Adicionais
  adicionaisConfig.forEach((item, i) => {
    total += (quantidadesAdicionais[i] || 0) * item.valor;
  });

  // Atualiza na tela (Se o elemento existir)
  const totalEl = document.getElementById("valorTotal");
  if (totalEl) totalEl.textContent = `R$ ${total.toFixed(2)}`;

  return total;
}


// ==========================================
// Funções Finais (Resumo e Envio)
// ==========================================

function getResumoPedido() {
    // Adiciona os adicionais ao objeto de dados
    pedidoData.adicionais = adicionaisConfig
        .map((item, i) => {
            const qtd = quantidadesAdicionais[i] || 0;
            if (qtd > 0) return { nome: item.nome, quantidade: qtd, valor: item.valor };
            return null;
        })
        .filter(Boolean);
        
    pedidoData.valorTotal = atualizarValorTotal();

    // Cria a string para o resumo visual
    const adicionaisParaResumo = pedidoData.adicionais
        .map(item => `${item.quantidade}x ${item.nome}`)
        .join(", ") || "Nenhum";

    // Converte a data de 'dd/mm/yyyy' para 'yyyy-mm-dd' (formato SQL)
    const dataParts = pedidoData.dataFesta.split('/');
    const dataFestaSQL = dataParts.length === 3 ? `${dataParts[2]}-${dataParts[1]}-${dataParts[0]}` : null; 

    return {
        // Dados para o PHP
        ...pedidoData,
        data_evento_sql: dataFestaSQL,
        // Dados para o WhatsApp/Resumo visual
        adicionaisParaResumo: adicionaisParaResumo,
        data: pedidoData.dataFesta,
    };
}


function gerarResumo() {
  const resumo = getResumoPedido();
  document.getElementById("resumo").innerHTML = `
    <p class="descricaoResumo"><strong>Cliente:</strong> ${resumo.nomeCliente}</p>
    <p class="descricaoResumo"><strong>Ocasião:</strong> ${resumo.tipoFesta}</p>
    <p class="descricaoResumo"><strong>Tema:</strong> ${resumo.tema}</p>
    <p class="descricaoResumo"><strong>Tamanho:</strong> ${resumo.tamanhoSelecionado === 0 ? "Festa na Mesa" : "Festão (EM BREVE)"}</p>
    <p class="descricaoResumo"><strong>Combo:</strong> ${['Essencial', 'Fantástico', 'Inesquecível'][resumo.comboSelecionado] || 'Não selecionado'} - ${resumo.mesaAtivada ? "Com mesa (+R$10)" : "Sem mesa"}</p>
    <p class="descricaoResumo"><strong>Homenageado(s):</strong> ${resumo.nomeHomenageado} ${resumo.idadeHomenageado ? `(${resumo.idadeHomenageado} anos)` : ""}</p>
    <p class="descricaoResumo"><strong>Adicionais:</strong> ${resumo.adicionaisParaResumo}</p>
    <p class="descricaoResumo"><strong>Data de Retirada:</strong> ${resumo.data}</p>
    <p class="descricaoResumo"><strong>Pagamento:</strong> ${resumo.formaPagamento}</p>
    <p class="descricaoResumo"><strong>Valor Total:</strong> R$ ${resumo.valorTotal.toFixed(2)}</p>
  `;
}

// ... (Funções enviarWhatsApp e enviarPedido permanecem como no passo anterior, usando fetch('../salvar_pedido.php'))
// ...
// O restante das funções auxiliares (mostrarTemas, filtrarTemas, etc.) devem ser implementadas usando os novos IDs e estrutura.