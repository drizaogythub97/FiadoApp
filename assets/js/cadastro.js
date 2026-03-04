document.addEventListener("DOMContentLoaded", function () {

    const clienteBusca = document.getElementById("clienteBusca");
    const dropdown = document.getElementById("clienteDropdown");
    const clienteIdInput = document.getElementById("cliente_id");

    clienteBusca.addEventListener("input", async function () {

        const termo = this.value.trim();
        clienteIdInput.value = "";

        if (termo.length < 2) {
            dropdown.innerHTML = "";
            dropdown.style.display = "none";
            return;
        }

        const response = await fetch(`/api/buscar_clientes.php?q=${encodeURIComponent(termo)}`);
        const clientes = await response.json();

        dropdown.innerHTML = "";

        if (!clientes.length) {
            dropdown.style.display = "none";
            return;
        }

        clientes.forEach(cliente => {

            const item = document.createElement("div");
            item.classList.add("autocomplete-item");

            item.textContent = `${cliente.nome} ${cliente.sobrenome} (${cliente.referencia || 'Sem referência'})`;

            item.addEventListener("click", function () {

                clienteIdInput.value = cliente.id;

                document.getElementById("nome").value = cliente.nome;
                document.getElementById("sobrenome").value = cliente.sobrenome;
                document.getElementById("referencia").value = cliente.referencia || "";
                document.getElementById("telefone").value = cliente.telefone || "";

                dropdown.style.display = "none";
                showToast("Cliente selecionado com sucesso!");

            });

            dropdown.appendChild(item);
        });

        dropdown.style.display = "block";

    });

    document.addEventListener("click", function(e){
        if (!e.target.closest(".autocomplete-group")) {
            dropdown.style.display = "none";
        }
    });

});

// =========================
// PRODUTOS DINÂMICOS
// =========================

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

    if(e.target.classList.contains("quantidade") || 
       e.target.classList.contains("valorUnitario")) {

        calcularTotal();
    }

});


// =========================
// SALVAR VENDA
// =========================

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
        cliente_id: document.getElementById("cliente_id").value,
        nome: document.getElementById("nome").value,
        sobrenome: document.getElementById("sobrenome").value,
        referencia: document.getElementById("referencia").value,
        telefone: document.getElementById("telefone").value,
        data_compra: document.getElementById("data_compra").value,
        data_vencimento: document.getElementById("data_vencimento").value,
        itens: produtos
    };

    try {

        const response = await fetch("/api/salvar_venda.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(dados)
        });

        const resultado = await response.json();

        if(resultado.status === "sucesso") {

            showToast("Venda cadastrada com sucesso!");

            setTimeout(() => {
                location.reload();
            }, 2000);

        } else {

            showToast(resultado.mensagem || "Erro ao cadastrar venda.", "error");
        }

    } catch (error) {

        console.error("Erro ao salvar venda:", error);
        showToast("Erro inesperado ao salvar venda.", "error");
    }
}