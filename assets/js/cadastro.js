let totalGeral = 0;

function adicionarProduto() {

    const container = document.getElementById("produtos");

    const wrapper = document.createElement("div");
    wrapper.classList.add("produto-wrapper");

    wrapper.innerHTML = `
        <button type="button" class="btn-remover">X</button>
        <div class="produto">
            <input type="number" min="1" placeholder="Qtd" class="quantidade">
            <input type="text" placeholder="Descrição" class="descricao">
            <input type="number" step="0.01" placeholder="Valor Unitário" class="valorUnitario">
        </div>
    `;

    wrapper.querySelector(".btn-remover").onclick = function () {
        wrapper.remove();
        calcularTotal();
    };

    container.appendChild(wrapper);
}

function calcularTotal() {
    totalGeral = 0;

    const produtos = document.querySelectorAll(".produto");

    produtos.forEach(prod => {
        const qtd = parseFloat(prod.querySelector(".quantidade").value) || 0;
        const valor = parseFloat(prod.querySelector(".valorUnitario").value) || 0;
        totalGeral += qtd * valor;
    });

    document.getElementById("totalGeral").innerText = totalGeral.toFixed(2);
}

document.addEventListener("input", function(e){
    if(e.target.classList.contains("quantidade") || e.target.classList.contains("valorUnitario")){
        calcularTotal();
    }
});

async function salvarVenda() {

    const produtos = [];

    document.querySelectorAll(".produto").forEach(prod => {
        produtos.push({
            quantidade: prod.querySelector(".quantidade").value,
            descricao: prod.querySelector(".descricao").value,
            valor_unitario: prod.querySelector(".valorUnitario").value
        });
    });

    const dados = {
        nome: document.getElementById("nome").value,
        referencia: document.getElementById("referencia").value,
        telefone: document.getElementById("telefone").value,
        data_compra: document.getElementById("data_compra").value,
        data_vencimento: document.getElementById("data_vencimento").value,
        itens: produtos
    };

    const response = await fetch("api/salvar_venda.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(dados)
    });

    const resultado = await response.json();

    document.getElementById("mensagem").innerText = resultado.mensagem;

    if(resultado.status === "sucesso"){
        location.reload();
    }
}