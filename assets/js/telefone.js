/**
 * FiadoApp — Máscara de telefone automática
 * Suporta 8 dígitos: (XX) XXXX-XXXX
 * Suporta 9 dígitos: (XX) XXXXX-XXXX
 *
 * Pronto para integração com WhatsApp Business API:
 * Use telDigitos(input) para obter o número limpo no formato E.164
 * Ex: "55" + telDigitos(input) → "+5511999999999"
 */

function aplicarMascaraTelefone(input) {
    if (!input) return;

    input.addEventListener('input', _formatarTelefone);
    input.addEventListener('keydown', function(e) {
        // Permite: backspace, delete, tab, setas, home, end
        if ([8, 46, 9, 27, 13, 35, 36, 37, 38, 39, 40].includes(e.keyCode)) return;
        // Bloqueia qualquer tecla não-numérica
        if ((e.shiftKey || e.ctrlKey || e.metaKey) && e.keyCode !== 86) return;
        if (e.keyCode < 48 || e.keyCode > 57) {
            if (e.keyCode < 96 || e.keyCode > 105) e.preventDefault();
        }
    });
}

function _formatarTelefone(e) {
    const input = e.target;
    // Preserva cursor para não pular ao final enquanto edita
    const digits = input.value.replace(/\D/g, '').slice(0, 11);
    input.value = _buildMascara(digits);
    // Salva dígitos limpos para uso futuro (API WhatsApp)
    input.dataset.digitos = digits;
}

function _buildMascara(digits) {
    if (!digits) return '';
    if (digits.length <= 2)  return '(' + digits;
    if (digits.length <= 6)  return '(' + digits.slice(0,2) + ') ' + digits.slice(2);
    if (digits.length <= 10) {
        // 8 dígitos: (XX) XXXX-XXXX
        return '(' + digits.slice(0,2) + ') ' + digits.slice(2,6) + '-' + digits.slice(6);
    }
    // 9 dígitos: (XX) XXXXX-XXXX
    return '(' + digits.slice(0,2) + ') ' + digits.slice(2,7) + '-' + digits.slice(7);
}

/**
 * Retorna os dígitos limpos do campo (sem formatação)
 * Use para montar número E.164: "55" + telDigitos(el) → "5511999999999"
 */
function telDigitos(input) {
    return (input.dataset.digitos || input.value.replace(/\D/g, '')).slice(0, 11);
}

/**
 * Formata um número já salvo no banco ao carregar o campo
 */
function preencherTelefone(input, valor) {
    if (!input || !valor) return;
    const digits = valor.replace(/\D/g, '').slice(0, 11);
    input.value = _buildMascara(digits);
    input.dataset.digitos = digits;
}

// Auto-aplica a todos os campos com data-telefone ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-telefone]').forEach(aplicarMascaraTelefone);
});
