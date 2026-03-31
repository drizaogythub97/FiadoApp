/**
 * comprovante_imagem.js
 * Geração client-side de comprovante de quitação em PNG via Canvas.
 * Largura: 750px (compatível com memória mobile).
 *
 * API pública:
 *   gerarComprovanteImagem(dados)
 *   - dados.emitidoPor  string
 *   - dados.cliente     string
 *   - dados.referencia  string
 *   - dados.telefone    string
 *   - dados.dataEmissao string  "dd/mm/aaaa hh:mm"
 *   - dados.titulo      string  ex: "Quitação Parcial"
 *   - dados.vendas      [{id, data, itens, valor}]
 *   - dados.totalQuitado number
 *   - dados.saldoRestante number
 *   - dados.nota        string
 */

(function () {

  // ── Constantes de layout (750px de largura) ─────────────────────────────────
  const W            = 750;
  const PAD          = 50;
  const COL          = W - PAD * 2;   // 650px
  const HEADER_H     = 262;
  const BODY_TOP     = 52;
  const SEC_TITLE_H  = 50;
  const INFO_ROW_H   = 76;            // label 30px + value 46px
  const VENDA_ROW_H  = 90;
  const VENDA_GAP    = 10;
  const TOTAL_BLKH   = 118;
  const SALDO_BLKH   = 104;
  const NOTA_LINE_H  = 28;
  const FOOTER_H     = 58;
  const BODY_BOTTOM  = 42;

  // ── Utilitários ────────────────────────────────────────────────────────────
  function brl(v) {
    return 'R$ ' + parseFloat(v).toLocaleString('pt-BR',
      { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function truncate(ctx, text, maxW) {
    while (ctx.measureText(text).width > maxW && text.length > 6)
      text = text.slice(0, -4) + '…';
    return text;
  }

  function wrapText(ctx, text, maxW) {
    const words = text.split(' ');
    const lines = [];
    let line = '';
    for (const w of words) {
      const test = line ? line + ' ' + w : w;
      if (ctx.measureText(test).width > maxW) { lines.push(line); line = w; }
      else line = test;
    }
    if (line) lines.push(line);
    return lines;
  }

  // ── Cálculo de altura dinâmica ──────────────────────────────────────────────
  function calcHeight(dados) {
    const infoRows  = 5;
    const clienteH  = SEC_TITLE_H + infoRows * INFO_ROW_H + 16;
    const vendasH   = SEC_TITLE_H + dados.vendas.length * (VENDA_ROW_H + VENDA_GAP) + 24;
    const blocosH   = TOTAL_BLKH + 26
                    + (dados.saldoRestante > 0 ? SALDO_BLKH + 26 : 0);
    const notaLinhas = Math.ceil(dados.nota.split(' ').length / 7) + 2;
    const notaH     = notaLinhas * NOTA_LINE_H + 30;
    return HEADER_H + BODY_TOP + clienteH + vendasH + blocosH + notaH + FOOTER_H + BODY_BOTTOM;
  }

  // ── Desenho principal ───────────────────────────────────────────────────────
  function desenhar(canvas, dados) {
    const H = calcHeight(dados);
    canvas.width  = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, W, H);

    // Background escuro
    const bg = ctx.createLinearGradient(0, 0, 0, H);
    bg.addColorStop(0, '#16162a'); bg.addColorStop(1, '#0f0f1e');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    // ── Cabeçalho coral ──────────────────────────────────────────────────────
    const hGrad = ctx.createLinearGradient(0, 0, W, HEADER_H);
    hGrad.addColorStop(0, '#e8624a'); hGrad.addColorStop(1, '#c84336');
    ctx.fillStyle = hGrad;
    ctx.beginPath();
    ctx.moveTo(0, 0); ctx.lineTo(W, 0);
    ctx.lineTo(W, HEADER_H - 48);
    ctx.quadraticCurveTo(W, HEADER_H, W - 68, HEADER_H);
    ctx.lineTo(68, HEADER_H);
    ctx.quadraticCurveTo(0, HEADER_H, 0, HEADER_H - 48);
    ctx.closePath();
    ctx.fill();

    // Círculo de fundo + logo
    ctx.fillStyle = 'rgba(255,255,255,0.18)';
    ctx.beginPath(); ctx.arc(W / 2, 78, 56, 0, Math.PI * 2); ctx.fill();

    const logo = window._fiadoLogo;
    if (logo && logo.complete && logo.naturalWidth > 0) {
      ctx.save();
      ctx.beginPath(); ctx.arc(W / 2, 78, 46, 0, Math.PI * 2); ctx.clip();
      ctx.drawImage(logo, W / 2 - 46, 32, 92, 92);
      ctx.restore();
    } else {
      ctx.fillStyle = '#fff'; ctx.font = 'bold 50px Arial';
      ctx.textAlign = 'center'; ctx.fillText('F', W / 2, 102);
    }

    ctx.textAlign = 'center';

    // "FiadoApp" — pequeno, subordinado ao título principal
    ctx.font = '500 18px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.66)';
    ctx.fillText('FiadoApp', W / 2, 156);

    // "Comprovante de Quitação" — TÍTULO PRINCIPAL (maior que FiadoApp)
    ctx.font = 'bold 34px Arial';
    ctx.fillStyle = '#fff';
    ctx.fillText('Comprovante de Quitação', W / 2, 196);

    // Subtítulo (tipo da quitação)
    ctx.font = '500 20px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.68)';
    ctx.fillText(dados.titulo, W / 2, 233);

    // ── Corpo ────────────────────────────────────────────────────────────────
    ctx.textAlign = 'left';
    let y = HEADER_H + BODY_TOP;

    function sectionTitle(title) {
      ctx.font = 'bold 22px Arial'; ctx.fillStyle = '#e8624a';
      ctx.fillText(title.toUpperCase(), PAD, y);
      ctx.strokeStyle = '#e8624a'; ctx.lineWidth = 1.5; ctx.globalAlpha = 0.3;
      ctx.beginPath(); ctx.moveTo(PAD, y + 11); ctx.lineTo(W - PAD, y + 11); ctx.stroke();
      ctx.globalAlpha = 1;
      y += SEC_TITLE_H;
    }

    function infoRow(label, value) {
      ctx.font = '18px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.46)';
      ctx.fillText(label, PAD, y); y += 30;
      ctx.font = 'bold 24px Arial'; ctx.fillStyle = '#fff';
      ctx.fillText(value, PAD, y); y += 46;
    }

    sectionTitle('Dados do Cliente');
    infoRow('Cliente',         dados.cliente     || '—');
    infoRow('Referência',      dados.referencia  || '—');
    infoRow('Telefone',        dados.telefone    || '—');
    infoRow('Registrado por',  dados.emitidoPor  || '—');
    infoRow('Data de emissão', dados.dataEmissao || '—');
    y += 16;

    sectionTitle('Vendas Quitadas');
    dados.vendas.forEach((v, i) => {
      const rowBg = i % 2 === 0 ? 'rgba(255,255,255,0.04)' : 'rgba(255,255,255,0.08)';
      ctx.fillStyle = rowBg;
      ctx.beginPath();
      ctx.roundRect(PAD - 18, y - 10, COL + 36, VENDA_ROW_H, 10);
      ctx.fill();

      ctx.font = 'bold 22px Arial'; ctx.fillStyle = '#e8624a'; ctx.textAlign = 'left';
      ctx.fillText(`#${v.id}`, PAD, y + 22);

      ctx.font = '17px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.5)'; ctx.textAlign = 'right';
      ctx.fillText(v.data, W - PAD, y + 22);

      ctx.textAlign = 'left'; ctx.font = '20px Arial';
      ctx.fillStyle = 'rgba(255,255,255,0.80)';
      ctx.fillText(truncate(ctx, v.itens, COL - 140), PAD, y + 58);

      ctx.font = 'bold 26px Arial'; ctx.fillStyle = '#fff'; ctx.textAlign = 'right';
      ctx.fillText(brl(v.valor), W - PAD, y + 58);
      ctx.textAlign = 'left';

      y += VENDA_ROW_H + VENDA_GAP;
    });

    y += 24;

    // ── Bloco total ───────────────────────────────────────────────────────────
    const tGrad = ctx.createLinearGradient(PAD - 18, y, W - PAD + 18, y);
    tGrad.addColorStop(0, '#e8624a'); tGrad.addColorStop(1, '#c84336');
    ctx.fillStyle = tGrad;
    ctx.beginPath(); ctx.roundRect(PAD - 18, y, COL + 36, TOTAL_BLKH, 14); ctx.fill();

    ctx.font = '21px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.82)'; ctx.textAlign = 'center';
    ctx.fillText('VALOR TOTAL QUITADO', W / 2, y + 38);
    ctx.font = 'bold 60px Arial'; ctx.fillStyle = '#fff';
    ctx.fillText(brl(dados.totalQuitado), W / 2, y + 100);

    y += TOTAL_BLKH + 26;

    // ── Bloco saldo restante ──────────────────────────────────────────────────
    if (dados.saldoRestante > 0) {
      ctx.fillStyle = 'rgba(255,180,60,0.10)';
      ctx.strokeStyle = '#f0a030'; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.roundRect(PAD - 18, y, COL + 36, SALDO_BLKH, 14);
      ctx.fill(); ctx.stroke();

      ctx.font = '20px Arial'; ctx.fillStyle = '#f0a030'; ctx.textAlign = 'center';
      ctx.fillText('SALDO RESTANTE EM ABERTO', W / 2, y + 34);
      ctx.font = 'bold 48px Arial'; ctx.fillStyle = '#ffd080';
      ctx.fillText(brl(dados.saldoRestante), W / 2, y + 86);

      y += SALDO_BLKH + 26;
    }

    // ── Nota final (wrap automático) ──────────────────────────────────────────
    y += 12;
    ctx.font = '17px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.34)'; ctx.textAlign = 'center';
    const linhasNota = wrapText(ctx, dados.nota, COL);
    linhasNota.forEach(l => { ctx.fillText(l, W / 2, y); y += NOTA_LINE_H; });

    // ── Rodapé ────────────────────────────────────────────────────────────────
    y += 20;
    ctx.font = '16px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.22)'; ctx.textAlign = 'center';
    ctx.fillText('Gerado pelo FiadoApp', W / 2, y);
  }

  // ── Carregamento de logo (com cache) ────────────────────────────────────────
  function ensureLogo(callback) {
    if (window._fiadoLogo !== undefined) { callback(); return; }
    const img = new Image();
    img.onload  = () => { window._fiadoLogo = img; callback(); };
    img.onerror = () => { window._fiadoLogo = null; callback(); };
    img.src = '/assets/img/logo.png';
  }

  // ── Download compatível com WebView Android, iOS e browser desktop ──────────
  function baixar(canvas, filename) {
    // Android WebView: usa interface nativa (blob URL não funciona via DownloadListener)
    if (window.FiadoAppNativo && typeof window.FiadoAppNativo.downloadPng === 'function') {
      try {
        const dataUrl = canvas.toDataURL('image/png');
        window.FiadoAppNativo.downloadPng(dataUrl, filename);
      } catch (e) {
        if (typeof showToast === 'function') showToast('Erro ao gerar imagem.', 'error');
      }
      return;
    }

    // Browser / iOS Safari
    canvas.toBlob(function (blob) {
      if (!blob) {
        if (typeof showToast === 'function') showToast('Erro ao gerar imagem.', 'error');
        return;
      }
      const url   = URL.createObjectURL(blob);
      const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
      if (isIOS) {
        // iOS Safari não suporta download direto — abre em nova aba para salvar
        window.open(url, '_blank');
        setTimeout(() => URL.revokeObjectURL(url), 30000);
      } else {
        const a = document.createElement('a');
        a.download = filename;
        a.href = url;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 10000);
      }
    }, 'image/png');
  }

  // ── Função pública ──────────────────────────────────────────────────────────
  window.gerarComprovanteImagem = function (dados) {
    ensureLogo(() => {
      try {
        const canvas = document.createElement('canvas');
        desenhar(canvas, dados);
        baixar(canvas, 'comprovante_quitacao.png');
      } catch (e) {
        console.error('gerarComprovanteImagem:', e);
        if (typeof showToast === 'function') showToast('Erro ao gerar imagem.', 'error');
      }
    });
  };

})();
