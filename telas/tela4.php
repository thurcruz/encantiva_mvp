<section id="tela4" class="tela">
  <img src="assets/logo_horizontal.svg" alt="Logo Encantiva">
  <h2>Quem está comemorando?</h2>
  <p>Dados do(s) homenageado(s)</p>
  <input type="text" id="nomeHomenageado" class="input-padrao" placeholder="Nome do homenageado" oninput="salvarDadosTela(4)">
  <input type="number" id="idadeHomenageado" class="input-padrao" placeholder="Idade (se aplicável)" oninput="salvarDadosTela(4)">
  
  <h5 class="obs"> Insira mais de um homenageado se necessário.</h5>
  
  <div class="buttons">
    <button class="btn btn-primary" onclick="proximaTela(5)">Próximo</button>
    <button class="btn btn-secondary" onclick="voltarTela(3)">Voltar</button>
  </div>
</section>