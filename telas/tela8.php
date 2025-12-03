<section id="tela8" class="tela"> 
  <img src="assets/logo_horizontal.svg" alt="Logo Encantiva"> 
  <h2>Adicionais</h2> 
  <p>Selecione os adicionais desejados:</p> 
      <div class="adicionais-button"> 
        <div class="adicionais-info"> 
          <span id="adicionais-label"></span> 
          <span id="desc-adicionais">Obs: Desative caso deseje só a decoração da festa!</span> 
        </div> 
        <div id="switchAdicionais" onclick="toggleAdicionais()"> 
          <button></button> 
          <span></span> 
        </div> 
    </div> 

  <div id="adicionaisContainer" class="adicionais-container"> 
    </div> 

   <h5 class="obs"> Clique no " + " para adicionar a quantidade de porções que desejar.
    Clique no " - " para Retirar a quantidade de porções selecionadas.
   </h5>
  <div class="containerValorTotal"> 
    <div class="Total">Total:</div> 
    <div id="totalAdicionais"></div> 
  </div>

  <div class="buttons"> 
    <button class="btn btn-primary" onclick="proximaTela(9)">Próximo</button> 
    <button class="btn btn-secondary" onclick="voltarTela(7)">Voltar</button> 
  </div>
</section>