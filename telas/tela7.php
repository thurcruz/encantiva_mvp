<section id="tela7" class="tela">
  <img src="assets/logo_horizontal.svg" alt="Logo Encantiva"> 
  <h2>Escolha seu combo</h2> 
  <p>Selecione de acordo com a sua preferência:</p> 
  <div class="mesa-container"> 
      <div class="mesa-info"> 
        <span id="mesa-label">Adicionar mesa (+R$10)</span> 
        <span id="desc-mesa">Obs: Os valores do combo abaixo não inclui mesa!</span> 
      </div> 
      <div id="switch" onclick="toggleMesa()"> 
        <button></button> 
        <span></span> 
      </div> 
  </div> 
    
    <div class="cards-wrapper"> 
    <div class="cards-combos" id="cardsCombos"> 
      <div class="card-combo" id="combo-essencial" onclick="selecionarCombo(0)"> 
      <h3 class="combo-nome">Essencial</h3> 
      <div class="combo-itens"> 
        <h4 class="item">Inclui:</h4> 
        <p class="item">1 Mini painel redondo</p> 
        <p class="item">1 Boleira</p> 
        <p class="item">3 Bandeijas</p> 
        <p class="item">1 Jarro c/ flores</p> 
        <p class="item">1 Totem</p>
      </div>
      <p class="valor" id="valor-essencial">R$ 39,90</p> 
    
    </div> <div class="card-combo" id="combo-fantastico" onclick="selecionarCombo(1)"> 
      <h3 class="combo-nome">Fantástico</h3> <div class="combo-itens"> 
        <h4 class="item">Inclui:</h4> 
        <p class="item">1 Mini painel redondo</p>
        <p class="item">1 Boleira</p> 
        <p class="item">6 Bandeijas</p> 
        <p class="item">1 Jarro c/ flores</p> 
        <p class="item">2 Totens</p> 
        <p class="item">1 Escada</p> 
        <p class="item">1 Bola Transversal</p> 
      </div> 
      
      <p class="valor" id="valor-fantastico">R$ 59,90</p> 
    
    </div> <div class="card-combo" id="combo-inesquecivel" onclick="selecionarCombo(2)"> 
      <h3 class="combo-nome">Inesquecível</h3> 
      <div class="combo-itens"> 
        <h4 class="item">Inclui:</h4> 
        <p class="item">1 painel redondo</p> 
        <p class="item">1 painel romano</p> 
        <p class="item">1 Boleira</p> 
        <p class="item">8 Bandeijas</p> 
        <p class="item">2 Jarro c/ flores</p> 
        <p class="item">3 Totens</p>
        <p class="item">1 Escada</p> 
        <p class="item">Mesa secundária</p> 
        <p class="item">1 Escada</p>
        <p class="item">1 Bola Transversal</p> 
        </div> 
         <p class="valor" id="valor-inesquecivel">R$ 109,90</p> 
        </div> 
      </div> 
    
    </div> <div class="scroll-buttons"> 
      <button class="scroll-btn" id="scroll-left" onclick="scrollCards(-1)"> 
        <img src="assets/left-arrow.svg" alt="Esquerda"> 
      </button> <button class="scroll-btn" id="scroll-right" onclick="scrollCards(1)"> 
        <img src="assets/right-arrow.svg" alt="Direita"> </button> 
      
      </div> 
      <h5 class="obs"> Obs: Para mudança de algum intem deve solicitar no WhatsApp depois de confirmar o pedido.</h5>
      <div class="containerValorTotal"> 
        
        <div class="Total">Total:</div> 
        <div id="valorTotal">R$ 0,00</div> 
      </div> <div class="buttons"> 
        
    </div> <div class="buttons"> 
      <button class="btn btn-primary" onclick="proximaTela(8)">Próximo</button> 
      <button class="btn btn-secondary" onclick="voltarTela(6)">Voltar</button> 
    </div>
</section>