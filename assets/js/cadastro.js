document.addEventListener("DOMContentLoaded", function () {

    // ── Flatpickr ─────────────────────────────────────────────────────────
    const fpOpts = {
        locale:      'pt',
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   'd/m/Y',
        allowInput:  false,
    };

    const fpCompra = flatpickr("#data_compra", {
        ...fpOpts,
        onChange: function(selectedDates) {
            if (!selectedDates[0]) return;
            const venc = new Date(selectedDates[0]);
            venc.setDate(venc.getDate() + 30);
            fpVencimento.setDate(venc, true);
        }
    });

    const fpVencimento = flatpickr("#data_vencimento", { ...fpOpts });

    // ── Máscara de telefone ───────────────────────────────────────────────
    const telInput = document.getElementById("telefone");
    if (telInput) aplicarMascaraTelefone(telInput);

    // ── Pré-preencher cliente vindo de ?cliente_id ────────────────────────
    if (window.PRE_CLIENTE) {
        const c = window.PRE_CLIENTE;
        document.getElementById('cliente_id').value = c.id;
        document.getElementById('nome').value       = c.nome       || '';
        document.getElementById('sobrenome').value  = c.sobrenome  || '';
        document.getElementById('referencia').value = c.referencia || '';
        if (telInput && c.telefone) preencherTelefone(telInput, c.telefone);
        document.getElementById('clienteBusca').value =
            c.nome + (c.sobrenome ? ' ' + c.sobrenome : '');
        showToast('Cliente ' + c.nome + ' pré-selecionado!');
    }

    // ── Autocomplete de cliente ───────────────────────────────────────────
    const clienteBusca   = document.getElementById("clienteBusca");
    const dropdown       = document.getElementById("clienteDropdown");
    const clienteIdInput = document.getElementById("cliente_id");

    if (!clienteBusca) return;

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

        if (!clientes.length) { dropdown.style.display = "none"; return; }

        clientes.forEach(cliente => {
            const item = document.createElement("div");
            item.classList.add("autocomplete-item");
            item.textContent = `${cliente.nome} ${cliente.sobrenome || ''} (${cliente.referencia || 'Sem referência'})`;
            item.addEventListener("click", function () {
                clienteIdInput.value = cliente.id;
                document.getElementById("nome").value      = cliente.nome;
                document.getElementById("sobrenome").value = cliente.sobrenome  || '';
                document.getElementById("referencia").value= cliente.referencia || '';
                preencherTelefone(document.getElementById("telefone"), cliente.telefone || "");
                dropdown.style.display = "none";
                showToast("Cliente selecionado com sucesso!");
            });
            dropdown.appendChild(item);
        });

        dropdown.style.display = "block";
    });

    document.addEventListener("click", function(e) {
        if (!e.target.closest(".autocomplete-group")) dropdown.style.display = "none";
    });

});

// ── PRODUTOS DINÂMICOS ────────────────────────────────────────────────────
function adicionarProduto() {
    const container = document.getElementById("produtos");
    const wrapper   = document.createElement("div");
    wrapper.classList.add("produto-wrapper");

    wrapper.innerHTML = `
        <button type="button" class="btn-remover">×</button>
        <div class="produto-card">
            <div class="produto-field">
                <label class="produto-label">Qtd</label>
                <input type="number" min="1" placeholder="0" class="quantidade">
            </div>
            <div class="produto-field">
                <label class="produto-label">Descrição</label>
                <input type="text" placeholder="Ex: Ração 15kg" class="descricao">
            </div>
            <div class="produto-field">
                <label class="produto-label">Valor Unit.</label>
                <input type="number" step="0.01" placeholder="0.00" class="valorUnitario">
            </div>
        </div>
    `;

    wrapper.querySelector(".btn-remover").onclick = function() {
        wrapper.remove();
        calcularTotal();
    };
    container.appendChild(wrapper);
}

function calcularTotal() {
    let total = 0;
    document.querySelectorAll(".produto-card").forEach(prod => {
        const qtd   = parseFloat(prod.querySelector(".quantidade").value)    || 0;
        const valor = parseFloat(prod.querySelector(".valorUnitario").value) || 0;
        total += qtd * valor;
    });
    document.getElementById("totalGeral").innerText = total.toLocaleString('pt-BR', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}

document.addEventListener("input", function(e) {
    if (e.target.classList.contains("quantidade") || e.target.classList.contains("valorUnitario")) {
        calcularTotal();
    }
});

// ── SALVAR VENDA ──────────────────────────────────────────────────────────
async function salvarVenda() {
    const produtos = [];
    document.querySelectorAll(".produto-card").forEach(prod => {
        produtos.push({
            quantidade:     prod.querySelector(".quantidade").value,
            descricao:      prod.querySelector(".descricao").value,
            valor_unitario: prod.querySelector(".valorUnitario").value
        });
    });

    const dados = {
        cliente_id:      document.getElementById("cliente_id").value,
        nome:            document.getElementById("nome").value,
        sobrenome:       document.getElementById("sobrenome").value,
        referencia:      document.getElementById("referencia").value,
        telefone:        document.getElementById("telefone").value,
        data_compra:     document.getElementById("data_compra").value,
        data_vencimento: document.getElementById("data_vencimento").value,
        observacao:      document.getElementById("observacao")?.value?.trim() || '',
        itens:           produtos
    };

    try {
        const response  = await fetch("/api/salvar_venda.php", {
            method:  "POST",
            headers: { "Content-Type": "application/json" },
            body:    JSON.stringify(dados)
        });
        const resultado = await response.json();

        if (resultado.status === "sucesso") {
            showToast("Venda cadastrada com sucesso!");
            // Redireciona para o detalhe do cliente em vez de recarregar a página
            setTimeout(() => {
                window.location.href = '/cliente_detalhe.php?id=' + resultado.cliente_id;
            }, 1500);
        } else {
            showToast(resultado.mensagem || "Erro ao cadastrar venda.", "error");
        }
    } catch(error) {
        console.error(error);
        showToast("Erro inesperado ao salvar venda.", "error");
    }
}
