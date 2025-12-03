<section id="tela2" class="tela">
  <img src="assets/logo_horizontal.svg" alt="Logo Encantiva">
  <h2>O que vamos realizar?</h2>
  <p>Selecione o tipo de festa:</p>
  <div class="grid-radios-type">
    <label><input type="radio" name="tipoFesta" value="Aniversário" onclick="salvarDadosTela(2)"> Aniversário</label>
    <label><input type="radio" name="tipoFesta" value="XV Anos" onclick="salvarDadosTela(2)"> XV Anos</label>
    <label><input type="radio" name="tipoFesta" value="Bodas" onclick="salvarDadosTela(2)"> Bodas</label>
    <label><input type="radio" name="tipoFesta" value="Mêsversário" onclick="salvarDadosTela(2)"> Mêsversário</label>
    <label><input type="radio" name="tipoFesta" value="Chá de Bebê" onclick="salvarDadosTela(2)"> Chá de Bebê</label>
    <label><input type="radio" name="tipoFesta" value="Chá Revelação" onclick="salvarDadosTela(2)"> Chá Revelação</label>
    <label><input type="radio" name="tipoFesta" value="Batizado" onclick="salvarDadosTela(2)"> Batizado</label>
    <label><input type="radio" name="tipoFesta" value="Outro" onclick="salvarDadosTela(2)"> Outro</label>
  </div>
  <div class="buttons">
    <button class="btn btn-primary" onclick="proximaTela(3)">Próximo</button>
    <button class="btn btn-secondary" onclick="voltarTela(1)">Voltar</button>
  </div>
</section>