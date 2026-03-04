// ===============================
// FUNÇÃO AUXILIAR DE DOWNLOAD
// ===============================
function baixarPDF(url) {

    if(!url) return;

    // força download direto
    const link = document.createElement("a");
    link.href = url;
    link.download = url.split("/").pop();
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ===============================
// FUNÇÃO PADRÃO DE PROCESSAMENTO
// ===============================
async function processarQuitacao(dados, mensagemSucesso){

    try {

        const response = await fetch("/api/quitar_cliente.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(dados)
        });

        const result = await response.json();

        if(result.status === "sucesso"){

            showToast(mensagemSucesso);

            // 🔥 AQUI ESTÁ A CORREÇÃO
            if(result.pdf){
                baixarPDF(result.pdf);
            }

            setTimeout(()=> location.reload(), 2000);

        } else {
            showToast(result.mensagem || "Erro na operação.", "error");
        }

    } catch(error){
        showToast("Erro inesperado.", "error");
        console.error(error);
    }
}

// ===============================
// QUITAR TODAS
// ===============================
function quitarTodas(cliente_id){

    if(!confirm("Confirmar quitação de TODAS as vendas?")) return;

    processarQuitacao(
        {
            cliente_id,
            tipo: "todas"
        },
        "Todas as vendas foram quitadas com sucesso!"
    );
}

// ===============================
// QUITAR SELECIONADAS
// ===============================
function quitarSelecionadas(cliente_id){

    const selecionadas = Array.from(
        document.querySelectorAll("input[name='vendas[]']:checked")
    ).map(cb => cb.value);

    if(selecionadas.length === 0){
        showToast("Selecione pelo menos uma venda.", "error");
        return;
    }

    processarQuitacao(
        {
            cliente_id,
            tipo: "selecionadas",
            vendas: selecionadas
        },
        "Vendas selecionadas quitadas com sucesso!"
    );
}

// ===============================
// QUITAR PARCIAL
// ===============================
function abrirQuitacaoParcial(cliente_id){

    const valor = prompt("Digite o valor a ser quitado:");

    if(!valor) return;

    processarQuitacao(
        {
            cliente_id,
            tipo: "parcial",
            valor: parseFloat(valor)
        },
        "Quitação parcial realizada com sucesso!"
    );
}