<section id="tela9" class="tela">
  <img src="assets/logo_horizontal.svg" alt="Logo Encantiva"> 
  <h2>Seus dados</h2> 
  <p>Insira suas informações para contato:</p> 

  <input type="text" id="nomeCliente" class="input-padrao" placeholder="Seu nome completo" oninput="salvarDadosTela(9)"> 
  <input type="tel" id="telefoneCliente" class="input-padrao" placeholder="Telefone (WhatsApp)" oninput="salvarDadosTela(9)"> 

  <h2>Forma de Pagamento</h2> <p>Escolha como deseja pagar:</p> 
  <div class="grid-radios-pay"> 
    <label>
      <input type="radio" name="formaPagamento" value="Pix" onclick="salvarDadosTela(9)"> Pix
    </label> 
    <label>
      <input type="radio" name="formaPagamento" value="Dinheiro" onclick="salvarDadosTela(9)"> Dinheiro
    </label> 
      <label>
        <input type="radio" name="formaPagamento" value="Cartão de Crédito" onclick="salvarDadosTela(9)"> Cartão de Crédito/Débito
      </label> 
  </div> 
    
  <div class="buttons"> 
    <button class="btn btn-primary" onclick="proximaTela(10)">Próximo</button> 
    <button class="btn btn-secondary" onclick="voltarTela(8)">Voltar</button> 
  </div>
</section>