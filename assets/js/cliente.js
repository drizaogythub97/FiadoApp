// ===============================
// SISTEMA DE MODAL CUSTOMIZADO
// ===============================

/**
 * Abre um modal flutuante com design do app.
 * @param {object} opts - opções do modal
 * @param {string} opts.titulo         - Título do modal
 * @param {string} opts.mensagem       - Mensagem explicativa
 * @param {string} [opts.inputLabel]   - Se definido, exibe campo de input com essa label
 * @param {string} [opts.inputPlaceholder] - Placeholder do input
 * @param {string} [opts.btnConfirmar] - Texto do botão confirmar
 * @param {string} [opts.btnClasse]    - Classe CSS do botão confirmar (success|primary|danger)
 * @returns {Promise<boolean|number|null>} - true (confirm), number (input), null (cancelou)
 */
/**
 * abrirModal — abre modal flutuante customizado.
 *
 * Parâmetros extras (opcionais):
 *   selectLabel  string           — label do <select> de formato
 *   selectOpcoes [{value, label}] — opções do <select>
 *   selectDefault string          — valor pré-selecionado (default: primeiro item)
 *
 * Retorno quando selectOpcoes NÃO fornecido:
 *   true (confirm) | number (input) | null (cancelou)
 *
 * Retorno quando selectOpcoes fornecido:
 *   { formato: string, valor: number|null } | null (cancelou)
 */
function abrirModal({ titulo, mensagem, inputLabel, inputPlaceholder,
                      btnConfirmar = 'Confirmar', btnClasse = 'success',
                      selectLabel, selectOpcoes, selectDefault }) {
    return new Promise((resolve) => {

        document.getElementById('appModal')?.remove();

        const temInput  = !!inputLabel;
        const temSelect = Array.isArray(selectOpcoes) && selectOpcoes.length > 0;

        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'appModal';

        const opcoesHTML = temSelect
            ? selectOpcoes.map(o =>
                `<option value="${o.value}"${o.value === selectDefault ? ' selected' : ''}>${o.label}</option>`
              ).join('')
            : '';

        overlay.innerHTML = `
            <div class="modal-card" role="dialog" aria-modal="true">
                <div class="modal-title">${titulo}</div>
                <div class="modal-message">${mensagem}</div>
                ${temInput ? `
                    <label class="modal-input-label">${inputLabel}</label>
                    <input
                        type="number"
                        id="modalInputValor"
                        class="modal-input"
                        placeholder="${inputPlaceholder || 'Ex: 50.00'}"
                        step="0.01"
                        min="0.01"
                        inputmode="decimal"
                    >
                ` : ''}
                ${temSelect ? `
                    <label class="modal-input-label" style="margin-top:${temInput ? '12px' : '4px'};">${selectLabel || 'Formato'}</label>
                    <select id="modalSelectFormato" class="modal-select">
                        ${opcoesHTML}
                    </select>
                ` : ''}
                <div class="modal-actions">
                    <button id="modalBtnConfirmar" class="btn-${btnClasse}">${btnConfirmar}</button>
                    <button id="modalBtnCancelar"  class="btn-secondary">Cancelar</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        // Precisa de .active para aparecer (display:none por padrão no CSS)
        requestAnimationFrame(() => overlay.classList.add('active'));

        if (temInput) {
            setTimeout(() => document.getElementById('modalInputValor')?.focus(), 60);
        } else {
            setTimeout(() => document.getElementById('modalBtnConfirmar')?.focus(), 60);
        }

        function fechar(valor) { overlay.remove(); resolve(valor); }

        document.getElementById('modalBtnConfirmar').onclick = () => {
            const formato = temSelect
                ? document.getElementById('modalSelectFormato').value
                : null;

            if (temInput) {
                const inputEl = document.getElementById('modalInputValor');
                const val = parseFloat(inputEl.value);
                if (!val || val <= 0) {
                    inputEl.classList.add('modal-input-error');
                    inputEl.focus();
                    setTimeout(() => inputEl.classList.remove('modal-input-error'), 400);
                    return;
                }
                // Com select: retorna objeto; sem select: retorna float (compat.)
                fechar(temSelect ? { valor: val, formato } : val);
            } else {
                // Com select: retorna objeto; sem select: retorna true (compat.)
                fechar(temSelect ? { formato } : true);
            }
        };

        document.getElementById('modalBtnCancelar').onclick = () => fechar(null);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) fechar(null); });
        overlay.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') fechar(null);
            if (e.key === 'Enter' && !temInput) { e.preventDefault(); fechar(temSelect ? { formato: document.getElementById('modalSelectFormato').value } : true); }
        });
    });
}

// ===============================
// FUNÇÃO AUXILIAR DE DOWNLOAD
// Usa navegação direta por âncora para garantir compatibilidade com
// Android WebView (DownloadListener), desktop e mobile.
// Blob URLs NÃO disparam o DownloadListener do Android.
// ===============================
function baixarPDF(url) {
    if (!url) return;
    const a = document.createElement('a');
    a.href = url;
    a.style.cssText = 'position:absolute;top:-9999px;left:-9999px;';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// ===============================
// FUNÇÃO PADRÃO DE PROCESSAMENTO
// ===============================
// Opções de formato de comprovante (reutilizadas nas 3 funções de quitar)
const FORMATO_OPCOES = [
    { value: 'pdf',    label: '📄 PDF' },
    { value: 'imagem', label: '🖼️ Imagem (PNG)' },
];

async function processarQuitacao(dados, mensagemSucesso) {
    try {
        const response = await fetch('/api/quitar_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });

        const result = await response.json();

        if (result.status === 'sucesso') {
            showToast(mensagemSucesso);
            const formato = dados.formato || 'pdf';
            if (formato === 'imagem' && result.dados && typeof gerarComprovanteImagem === 'function') {
                gerarComprovanteImagem(result.dados);
            } else if (result.pdf) {
                baixarPDF(result.pdf);
            }
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast(result.mensagem || 'Erro na operação.', 'error');
        }

    } catch (error) {
        showToast('Erro inesperado.', 'error');
        console.error(error);
    }
}

// ===============================
// QUITAR TODAS
// ===============================
async function quitarTodas(cliente_id) {

    const resultado = await abrirModal({
        titulo:        'Quitar Todas as Vendas',
        mensagem:      'Todas as vendas ativas deste cliente serão marcadas como pagas. Esta ação não pode ser desfeita.',
        btnConfirmar:  '✓ Confirmar Quitação',
        btnClasse:     'success',
        selectLabel:   'Formato do comprovante',
        selectOpcoes:  FORMATO_OPCOES,
        selectDefault: 'pdf',
    });

    if (!resultado) return;

    processarQuitacao(
        { cliente_id, tipo: 'todas', formato: resultado.formato },
        'Todas as vendas foram quitadas com sucesso!'
    );
}

// ===============================
// QUITAR SELECIONADAS
// ===============================
async function quitarSelecionadas(cliente_id) {

    const selecionadas = Array.from(
        document.querySelectorAll("input[name='vendas[]']:checked")
    ).map(cb => cb.value);

    if (selecionadas.length === 0) {
        showToast('Selecione pelo menos uma venda.', 'error');
        return;
    }

    const plural = selecionadas.length > 1;

    const resultado = await abrirModal({
        titulo:       `Quitar ${selecionadas.length} Venda${plural ? 's' : ''} Selecionada${plural ? 's' : ''}`,
        mensagem:     `${plural ? 'As' : 'A'} ${selecionadas.length} venda${plural ? 's' : ''} selecionada${plural ? 's' : ''} ser${plural ? 'ão' : 'á'} marcada${plural ? 's' : ''} como paga${plural ? 's' : ''}.`,
        btnConfirmar: '✓ Confirmar',
        btnClasse:    'success',
        selectLabel:  'Formato do comprovante',
        selectOpcoes: FORMATO_OPCOES,
        selectDefault:'pdf',
    });

    if (!resultado) return;

    processarQuitacao(
        { cliente_id, tipo: 'selecionadas', vendas: selecionadas, formato: resultado.formato },
        `${selecionadas.length} venda${plural ? 's' : ''} quitada${plural ? 's' : ''} com sucesso!`
    );
}

// ===============================
// QUITAR VALOR ESPECÍFICO (PARCIAL)
// ===============================
async function abrirQuitacaoParcial(cliente_id) {

    const resultado = await abrirModal({
        titulo:            'Quitar Valor Específico',
        mensagem:          'Informe o valor a ser quitado. O valor será abatido das vendas mais antigas até ser totalmente descontado.',
        inputLabel:        'Valor a quitar (R$)',
        inputPlaceholder:  'Ex: 50.00',
        btnConfirmar:      '✓ Confirmar',
        btnClasse:         'success',
        selectLabel:       'Formato do comprovante',
        selectOpcoes:      FORMATO_OPCOES,
        selectDefault:     'pdf',
    });

    if (!resultado) return;

    processarQuitacao(
        { cliente_id, tipo: 'parcial', valor: resultado.valor, formato: resultado.formato },
        'Quitação parcial realizada com sucesso!'
    );
}
