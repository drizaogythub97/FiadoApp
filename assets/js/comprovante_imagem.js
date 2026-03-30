/**
 * comprovante_imagem.js
 * Geração client-side de comprovante de quitação em PNG via Canvas.
 * Chamado por cliente.js após retorno de /api/quitar_cliente.php com formato=imagem.
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

(function() {

  // ── Constantes de layout (1080px de largura) ────────────────────────────────
  const W            = 1080;
  const PAD          = 72;
  const COL          = W - PAD * 2;
  const HEADER_H     = 390;
  const BODY_TOP     = 68;
  const SEC_TITLE_H  = 68;
  const INFO_ROW_H   = 108;
  const VENDA_ROW_H  = 130;
  const VENDA_GAP    = 14;
  const TOTAL_BLKH   = 170;
  const SALDO_BLKH   = 148;
  const NOTA_LINE_H  = 38;
  const FOOTER_H     = 80;
  const BODY_BOTTOM  = 50;

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

  function calcHeight(dados) {
    const infoRows   = 5; // cliente, ref, tel, emitidoPor, dataEmissao
    const clienteH   = SEC_TITLE_H + infoRows * INFO_ROW_H + 14;
    const vendasH    = SEC_TITLE_H + dados.vendas.length * (VENDA_ROW_H + VENDA_GAP) + 22;
    const blocosH    = TOTAL_BLKH + 30
                     + (dados.saldoRestante > 0 ? SALDO_BLKH + 30 : 0);
    // Estima linhas da nota (4 palavras por linha em média para 1080px)
    const notaLinhas = Math.ceil(dados.nota.split(' ').length / 7) + 1;
    const notaH      = notaLinhas * NOTA_LINE_H + 30;
    return HEADER_H + BODY_TOP + clienteH + vendasH + blocosH + notaH + FOOTER_H + BODY_BOTTOM;
  }

  function desenhar(canvas, dados) {
    const H   = calcHeight(dados);
    canvas.width  = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, W, H);

    // Background
    const bg = ctx.createLinearGradient(0, 0, 0, H);
    bg.addColorStop(0, '#16162a'); bg.addColorStop(1, '#0f0f1e');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    // ── Cabeçalho coral ──
    const hGrad = ctx.createLinearGradient(0, 0, W, HEADER_H);
    hGrad.addColorStop(0, '#e8624a'); hGrad.addColorStop(1, '#c84336');
    ctx.fillStyle = hGrad;
    ctx.beginPath();
    ctx.moveTo(0, 0); ctx.lineTo(W, 0);
    ctx.lineTo(W, HEADER_H - 70);
    ctx.quadraticCurveTo(W, HEADER_H, W - 100, HEADER_H);
    ctx.lineTo(100, HEADER_H);
    ctx.quadraticCurveTo(0, HEADER_H, 0, HEADER_H - 70);
    ctx.closePath();
    ctx.fill();

    // Círculo + logo
    ctx.fillStyle = 'rgba(255,255,255,0.18)';
    ctx.beginPath(); ctx.arc(W / 2, 118, 82, 0, Math.PI * 2); ctx.fill();

    const logo = window._fiadoLogo;
    if (logo && logo.complete && logo.naturalWidth > 0) {
      ctx.save();
      ctx.beginPath(); ctx.arc(W / 2, 118, 70, 0, Math.PI * 2); ctx.clip();
      ctx.drawImage(logo, W / 2 - 70, 48, 140, 140);
      ctx.restore();
    } else {
      ctx.fillStyle = '#fff'; ctx.font = 'bold 80px Arial';
      ctx.textAlign = 'center'; ctx.fillText('F', W / 2, 155);
    }

    ctx.textAlign = 'center'; ctx.fillStyle = '#fff';
    ctx.font = 'bold 62px Arial';
    ctx.fillText('FiadoApp', W / 2, 260);

    ctx.font = 'bold 46px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.97)';
    ctx.fillText('Comprovante de Quitação', W / 2, 318);

    ctx.font = '500 36px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.72)';
    ctx.fillText(dados.titulo, W / 2, 370);

    // ── Corpo ──
    ctx.textAlign = 'left';
    let y = HEADER_H + BODY_TOP;

    function sectionTitle(title) {
      ctx.font = 'bold 32px Arial'; ctx.fillStyle = '#e8624a';
      ctx.fillText(title.toUpperCase(), PAD, y);
      ctx.strokeStyle = '#e8624a'; ctx.lineWidth = 2; ctx.globalAlpha = 0.3;
      ctx.beginPath(); ctx.moveTo(PAD, y + 14); ctx.lineTo(W - PAD, y + 14); ctx.stroke();
      ctx.globalAlpha = 1;
      y += SEC_TITLE_H;
    }

    function infoRow(label, value) {
      ctx.font = '28px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.46)';
      ctx.fillText(label, PAD, y); y += 44;
      ctx.font = 'bold 36px Arial'; ctx.fillStyle = '#fff';
      ctx.fillText(value, PAD, y); y += 64;
    }

    sectionTitle('Dados do Cliente');
    infoRow('Cliente',         dados.cliente     || '—');
    infoRow('Referência',      dados.referencia  || '—');
    infoRow('Telefone',        dados.telefone    || '—');
    infoRow('Registrado por',  dados.emitidoPor  || '—');
    infoRow('Data de emissão', dados.dataEmissao || '—');
    y += 14;

    sectionTitle('Vendas Quitadas');
    dados.vendas.forEach((v, i) => {
      const rowBg = i % 2 === 0 ? 'rgba(255,255,255,0.04)' : 'rgba(255,255,255,0.08)';
      ctx.fillStyle = rowBg;
      ctx.beginPath();
      ctx.roundRect(PAD - 26, y - 14, COL + 52, VENDA_ROW_H, 12);
      ctx.fill();

      ctx.font = 'bold 30px Arial'; ctx.fillStyle = '#e8624a'; ctx.textAlign = 'left';
      ctx.fillText(`#${v.id}`, PAD, y + 28);

      ctx.font = '26px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.5)'; ctx.textAlign = 'right';
      ctx.fillText(v.data, W - PAD, y + 28);

      ctx.textAlign = 'left'; ctx.font = '28px Arial';
      ctx.fillStyle = 'rgba(255,255,255,0.78)';
      ctx.fillText(truncate(ctx, v.itens, COL - 200), PAD, y + 72);

      ctx.font = 'bold 36px Arial'; ctx.fillStyle = '#fff'; ctx.textAlign = 'right';
      ctx.fillText(brl(v.valor), W - PAD, y + 72);
      ctx.textAlign = 'left';

      y += VENDA_ROW_H + VENDA_GAP;
    });

    y += 22;

    // Bloco total
    const tGrad = ctx.createLinearGradient(PAD - 26, y, W - PAD + 26, y);
    tGrad.addColorStop(0, '#e8624a'); tGrad.addColorStop(1, '#c84336');
    ctx.fillStyle = tGrad;
    ctx.beginPath(); ctx.roundRect(PAD - 26, y, COL + 52, TOTAL_BLKH, 16); ctx.fill();

    ctx.font = '32px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.82)'; ctx.textAlign = 'center';
    ctx.fillText('VALOR TOTAL QUITADO', W / 2, y + 52);
    ctx.font = 'bold 84px Arial'; ctx.fillStyle = '#fff';
    ctx.fillText(brl(dados.totalQuitado), W / 2, y + 144);

    y += TOTAL_BLKH + 30;

    // Bloco saldo restante
    if (dados.saldoRestante > 0) {
      ctx.fillStyle = 'rgba(255,180,60,0.10)';
      ctx.strokeStyle = '#f0a030'; ctx.lineWidth = 3;
      ctx.beginPath(); ctx.roundRect(PAD - 26, y, COL + 52, SALDO_BLKH, 16);
      ctx.fill(); ctx.stroke();

      ctx.font = '30px Arial'; ctx.fillStyle = '#f0a030'; ctx.textAlign = 'center';
      ctx.fillText('SALDO RESTANTE EM ABERTO', W / 2, y + 46);
      ctx.font = 'bold 66px Arial'; ctx.fillStyle = '#ffd080';
      ctx.fillText(brl(dados.saldoRestante), W / 2, y + 120);

      y += SALDO_BLKH + 30;
    }

    // Nota final (com quebra de linha automática)
    y += 10;
    ctx.font = '26px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.34)'; ctx.textAlign = 'center';
    const linhasNota = wrapText(ctx, dados.nota, COL);
    linhasNota.forEach(l => { ctx.fillText(l, W / 2, y); y += NOTA_LINE_H; });

    // Rodapé
    y += 20;
    ctx.font = '24px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.22)'; ctx.textAlign = 'center';
    ctx.fillText('Gerado pelo FiadoApp', W / 2, y);
  }

  // ── Garante que a logo esteja carregada antes de desenhar ──────────────────
  function ensureLogo(callback) {
    if (window._fiadoLogo) { callback(); return; }
    const img = new Image();
    img.onload  = () => { window._fiadoLogo = img; callback(); };
    img.onerror = () => { window._fiadoLogo = null; callback(); };
    img.src = '/assets/img/logo.png';
  }

  // ── Função pública ──────────────────────────────────────────────────────────
  window.gerarComprovanteImagem = function(dados) {
    ensureLogo(() => {
      const canvas = document.createElement('canvas');
      desenhar(canvas, dados);
      const link = document.createElement('a');
      link.download = 'comprovante_quitacao.png';
      link.href     = canvas.toDataURL('image/png');
      link.click();
    });
  };

})();
