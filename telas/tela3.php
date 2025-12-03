<section id="tela3" class="tela">
  <img src="assets/logo_horizontal.svg" alt="Logo Encantiva">
  <h2>Qual será o tema da festa?</h2>
  <p>Veja os temas disponíveis (Tipo: <?php echo htmlspecialchars($_GET['tipo'] ?? 'Aniversário'); ?>):</p>
  
  <div class="select-busca">
    <input type="text" id="pesquisaTema" class="input-padrao" placeholder="Pesquisar tema..." 
    onclick="mostrarTemas()" oninput="filtrarTemas(); salvarDadosTela(3)">
    <div id="listaTemas" class="hidden">
      </div>

    <h2>Outro?</h2>
    <label class="label-temaOutro">
      <input type="checkbox" id="temaOutro" onchange="ativarTemaOutro(); salvarDadosTela(3)">
      Não achou o seu tema?
    </label>
    <input type="text" id="novoTema" class="input-padrao" placeholder="Digite seu tema" style="display:none;" oninput="salvarDadosTela(3)">
  </div>

  <h5 class="obs"> Caso selecione a opção “Outro”, informe o tema desejado para verificarmos a disponibilidade e retornarmos o contato.</h5>


  <div class="buttons">
    <button class="btn btn-primary" onclick="proximaTela(4)">Próximo</button>
    <button class="btn btn-secondary" onclick="voltarTela(2)">Voltar</button>
  </div>
</section>