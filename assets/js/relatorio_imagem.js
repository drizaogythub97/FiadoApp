/**
 * relatorio_imagem.js
 * Geração client-side de relatório de vendas em PNG via Canvas.
 * Largura: 750px (compatível com memória mobile). Altura dinâmica.
 *
 * API pública:
 *   gerarRelatorioImagem(vendas, meta)
 *   - vendas  [{id, nome, sobrenome, referencia, data_compra, data_vencimento,
 *               valor_total, status, itens:[{descricao, quantidade,
 *               valor_unitario, valor_total}]}]
 *   - meta    { emitidoPor, filtroStatus, periodo, dataEmissao }
 */

(function () {

  // ── Constantes de layout (750px de largura) ─────────────────────────────────
  const W             = 750;
  const PAD           = 50;
  const COL           = W - PAD * 2;   // 650px
  const HEADER_H      = 216;
  const BODY_TOP      = 44;
  const SUMMARY_H     = 154;
  const SUMMARY_GAP   = 36;
  const SEC_TITLE_H   = 54;
  const VENDA_HEAD_H  = 88;
  const ITEM_H        = 86;            // aumentado para melhor espaçamento
  const VENDA_FOOT_H  = 68;
  const VENDA_SEP     = 28;
  const TOTAL_H       = 82;
  const FOOTER_H      = 80;
  const BODY_BOTTOM   = 40;

  const STATUS_COR = { ATIVA: '#f0a030', PAGA: '#3ec98a' };

  // ── Utilitários ─────────────────────────────────────────────────────────────
  function brl(v) {
    return 'R$ ' + parseFloat(v).toLocaleString('pt-BR',
      { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function fmtData(iso) {
    if (!iso) return '—';
    const p = iso.split(' ')[0].split('-');
    return p.length < 3 ? iso : `${p[2]}/${p[1]}/${p[0]}`;
  }

  // ── Cálculo de altura dinâmica ──────────────────────────────────────────────
  function calcHeight(vendas) {
    let vendasH = 0;
    vendas.forEach(v => {
      const n = (v.itens || []).length || 1;
      vendasH += VENDA_HEAD_H + n * ITEM_H + VENDA_FOOT_H + VENDA_SEP;
    });
    return HEADER_H + BODY_TOP + SUMMARY_H + SUMMARY_GAP + SEC_TITLE_H
         + vendasH + TOTAL_H + FOOTER_H + BODY_BOTTOM;
  }

  // ── Desenho principal ───────────────────────────────────────────────────────
  function desenhar(canvas, vendas, meta) {
    const H = calcHeight(vendas);
    canvas.width  = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, W, H);

    const totalGeral  = vendas.reduce((s, v) => s + parseFloat(v.valor_total), 0);
    const totalAtivas = vendas.filter(v => v.status === 'ATIVA')
                              .reduce((s, v) => s + parseFloat(v.valor_total), 0);
    const totalPagas  = vendas.filter(v => v.status === 'PAGA')
                              .reduce((s, v) => s + parseFloat(v.valor_total), 0);

    // Background
    ctx.fillStyle = '#13131f';
    ctx.fillRect(0, 0, W, H);

    // Listra lateral coral
    const stripeGrad = ctx.createLinearGradient(0, 0, 0, H * 0.55);
    stripeGrad.addColorStop(0, '#e8624a');
    stripeGrad.addColorStop(1, 'rgba(232,98,74,0)');
    ctx.fillStyle = stripeGrad;
    ctx.fillRect(0, 0, 6, H);

    // ── Cabeçalho ──────────────────────────────────────────────────────────────
    const hGrad = ctx.createLinearGradient(0, 0, W, HEADER_H);
    hGrad.addColorStop(0, '#e8624a'); hGrad.addColorStop(1, '#b83628');
    ctx.fillStyle = hGrad;
    ctx.beginPath();
    ctx.moveTo(0, 0); ctx.lineTo(W, 0);
    ctx.lineTo(W, HEADER_H - 42);
    ctx.quadraticCurveTo(W, HEADER_H, W - 68, HEADER_H);
    ctx.lineTo(68, HEADER_H);
    ctx.quadraticCurveTo(0, HEADER_H, 0, HEADER_H - 42);
    ctx.closePath();
    ctx.fill();

    // Logo (menor, proporcional ao texto)
    const logoSz = 58;
    const logoX  = PAD + 4;
    const logoY  = 20;
    ctx.save();
    ctx.beginPath();
    ctx.arc(logoX + logoSz / 2, logoY + logoSz / 2, logoSz / 2, 0, Math.PI * 2);
    ctx.clip();
    const logo = window._fiadoLogo;
    if (logo && logo.complete && logo.naturalWidth > 0) {
      ctx.drawImage(logo, logoX, logoY, logoSz, logoSz);
    } else {
      ctx.fillStyle = 'rgba(255,255,255,0.25)'; ctx.fill();
      ctx.fillStyle = '#fff'; ctx.font = 'bold 34px Arial';
      ctx.textAlign = 'center';
      ctx.fillText('F', logoX + logoSz / 2, logoY + logoSz / 2 + 12);
    }
    ctx.restore();

    const textX = logoX + logoSz + 16;

    // "FiadoApp" — pequeno, subordinado ao título
    ctx.textAlign = 'left';
    ctx.font = '500 17px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.66)';
    ctx.fillText('FiadoApp', textX, logoY + 18);

    // "Relatório de Vendas" — TÍTULO PRINCIPAL (maior que FiadoApp)
    ctx.font = 'bold 30px Arial';
    ctx.fillStyle = '#fff';
    ctx.fillText('Relatório de Vendas', textX, logoY + 54);

    // Meta: emitido por / data
    ctx.font = '17px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.58)';
    ctx.fillText(`Emitido em ${meta.dataEmissao || ''} · por ${meta.emitidoPor || ''}`, textX, logoY + 82);

    // Filtros (canto direito)
    ctx.textAlign = 'right';
    ctx.font = '16px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.5)';
    if (meta.periodo) ctx.fillText(meta.periodo, W - PAD, 126);
    const filtroLabel = {
      '': 'Todas as vendas', ATIVA: 'Somente ativas', PAGA: 'Somente pagas'
    }[meta.filtroStatus || ''] || 'Todas as vendas';
    ctx.fillText(filtroLabel, W - PAD, 150);

    // ── Cards de resumo ────────────────────────────────────────────────────────
    let y = HEADER_H + BODY_TOP;
    const terceiroLabel = (meta.filtroStatus === 'PAGA') ? 'Total Pago'  : 'A Receber';
    const terceiroVal   = (meta.filtroStatus === 'PAGA') ? totalPagas    : totalAtivas;

    const cards = [
      { label: 'Registros',   value: String(vendas.length), cor: '#4a9eff' },
      { label: 'Total Geral', value: brl(totalGeral),        cor: '#e8624a' },
      { label: terceiroLabel, value: brl(terceiroVal),       cor: '#3ec98a' },
    ];
    const cardW = Math.floor((COL - 20) / 3);
    const cardH = SUMMARY_H - 20;

    cards.forEach((c, i) => {
      const cx = PAD + i * (cardW + 10);
      ctx.fillStyle = 'rgba(255,255,255,0.04)';
      ctx.beginPath(); ctx.roundRect(cx, y, cardW, cardH, 12); ctx.fill();
      ctx.strokeStyle = c.cor; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.roundRect(cx, y, cardW, cardH, 12); ctx.stroke();
      ctx.fillStyle = c.cor;
      ctx.beginPath(); ctx.roundRect(cx, y, cardW, 5, [3, 3, 0, 0]); ctx.fill();
      ctx.textAlign = 'center';
      ctx.font = '16px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.5)';
      ctx.fillText(c.label, cx + cardW / 2, y + 44);
      ctx.font = 'bold 23px Arial'; ctx.fillStyle = c.cor;
      let txt = c.value;
      while (ctx.measureText(txt).width > cardW - 12 && txt.length > 4) txt = txt.slice(0, -4) + '…';
      ctx.fillText(txt, cx + cardW / 2, y + 82);
      ctx.textAlign = 'left';
    });

    y += SUMMARY_H + SUMMARY_GAP;

    // ── Título seção vendas ───────────────────────────────────────────────────
    ctx.font = 'bold 21px Arial'; ctx.fillStyle = '#e8624a';
    ctx.fillText('VENDAS', PAD, y);
    ctx.strokeStyle = '#e8624a'; ctx.lineWidth = 1.5; ctx.globalAlpha = 0.3;
    ctx.beginPath(); ctx.moveTo(PAD, y + 10); ctx.lineTo(W - PAD, y + 10); ctx.stroke();
    ctx.globalAlpha = 1;
    y += SEC_TITLE_H;

    // ── Registros de venda ────────────────────────────────────────────────────
    vendas.forEach((v, vi) => {
      const itens  = v.itens || [];
      const sc     = STATUS_COR[v.status] || '#888';
      const blockH = VENDA_HEAD_H + itens.length * ITEM_H + VENDA_FOOT_H;

      // Fundo do bloco
      ctx.fillStyle = vi % 2 === 0 ? 'rgba(255,255,255,0.03)' : 'rgba(255,255,255,0.07)';
      ctx.beginPath(); ctx.roundRect(PAD - 20, y - 6, COL + 40, blockH + 12, 12); ctx.fill();

      // Borda esquerda colorida por status
      ctx.fillStyle = sc;
      ctx.beginPath(); ctx.roundRect(PAD - 20, y - 6, 5, blockH + 12, [3, 0, 0, 3]); ctx.fill();

      // ── Cabeçalho da venda ────────────────────────────────────────────────
      ctx.font = 'bold 24px Arial'; ctx.fillStyle = '#e8624a'; ctx.textAlign = 'left';
      ctx.fillText(`#${v.id}`, PAD + 10, y + 28);

      // Badge status
      const badgeW = 108, badgeH = 30;
      ctx.fillStyle = sc + '22'; ctx.strokeStyle = sc; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.roundRect(PAD + 10 + 68, y + 8, badgeW, badgeH, 7); ctx.fill(); ctx.stroke();
      ctx.font = 'bold 16px Arial'; ctx.fillStyle = sc; ctx.textAlign = 'center';
      ctx.fillText(v.status, PAD + 10 + 68 + badgeW / 2, y + 28);
      ctx.textAlign = 'left';

      // Nome do cliente
      const nome    = `${v.nome || ''}${v.sobrenome ? ' ' + v.sobrenome : ''}`;
      const nomeRef = v.referencia ? `${nome}  (${v.referencia})` : nome;
      ctx.font = 'bold 25px Arial'; ctx.fillStyle = '#fff';
      ctx.fillText(nomeRef, PAD + 10, y + 66);

      // Datas (canto direito)
      ctx.textAlign = 'right'; ctx.font = '16px Arial';
      ctx.fillStyle = 'rgba(255,255,255,0.48)';
      ctx.fillText(`Compra: ${fmtData(v.data_compra)}`,     W - PAD - 10, y + 26);
      ctx.fillText(`Vence: ${fmtData(v.data_vencimento)}`,  W - PAD - 10, y + 50);
      ctx.textAlign = 'left';

      y += VENDA_HEAD_H;

      // ── Itens da venda ────────────────────────────────────────────────────
      itens.forEach((it, ii) => {
        // Fundo alternado
        ctx.fillStyle = ii % 2 === 0 ? 'rgba(0,0,0,0.14)' : 'rgba(0,0,0,0.07)';
        ctx.fillRect(PAD - 14, y, COL + 28, ITEM_H);

        // Separador
        ctx.strokeStyle = 'rgba(255,255,255,0.06)'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(PAD - 14, y); ctx.lineTo(W - PAD + 14, y); ctx.stroke();

        const midY = y + ITEM_H / 2;

        // Ícone "›"
        ctx.font = '20px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.35)';
        ctx.textAlign = 'left';
        ctx.fillText('›', PAD + 12, midY + 7);

        // Descrição do produto — boa legibilidade
        ctx.font = '22px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.90)';
        ctx.fillText(it.descricao || it.desc || '', PAD + 36, midY + 7);

        // Quantidade × preço unitário (canto superior direito)
        ctx.textAlign = 'right';
        ctx.font = '16px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.50)';
        const unit = parseFloat(it.valor_unitario || it.unit || 0);
        ctx.fillText(
          `${it.quantidade || it.qtd || 1}×  R$ ${unit.toFixed(2).replace('.', ',')}`,
          W - PAD - 10, midY - 6
        );

        // Subtotal — destaque
        ctx.font = 'bold 24px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.92)';
        ctx.fillText(brl(it.valor_total || it.sub || 0), W - PAD - 10, midY + 22);
        ctx.textAlign = 'left';

        y += ITEM_H;
      });

      // ── Rodapé da venda ───────────────────────────────────────────────────
      ctx.fillStyle = 'rgba(232,98,74,0.10)';
      ctx.fillRect(PAD - 14, y, COL + 28, VENDA_FOOT_H);

      ctx.strokeStyle = 'rgba(232,98,74,0.25)'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(PAD - 14, y); ctx.lineTo(W - PAD + 14, y); ctx.stroke();

      const footMid = y + VENDA_FOOT_H / 2;
      ctx.font = 'bold 21px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.55)'; ctx.textAlign = 'left';
      ctx.fillText(
        `Total da venda  (${itens.length} iten${itens.length !== 1 ? 's' : ''})`,
        PAD + 10, footMid + 9
      );

      ctx.font = 'bold 34px Arial'; ctx.fillStyle = '#e8624a'; ctx.textAlign = 'right';
      ctx.fillText(brl(v.valor_total), W - PAD - 10, footMid + 11);
      ctx.textAlign = 'left';

      y += VENDA_FOOT_H + VENDA_SEP;
    });

    // ── Total geral ───────────────────────────────────────────────────────────
    y += 4;
    const tGrad = ctx.createLinearGradient(PAD - 20, y, W - PAD + 20, y);
    tGrad.addColorStop(0, '#e8624a'); tGrad.addColorStop(1, '#c84336');
    ctx.fillStyle = tGrad;
    ctx.beginPath(); ctx.roundRect(PAD - 20, y, COL + 40, TOTAL_H, 10); ctx.fill();

    const totMid = y + TOTAL_H / 2;
    ctx.font = 'bold 22px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.82)'; ctx.textAlign = 'left';
    ctx.fillText(
      `TOTAL GERAL  (${vendas.length} venda${vendas.length !== 1 ? 's' : ''})`,
      PAD + 10, totMid + 10
    );

    ctx.font = 'bold 36px Arial'; ctx.fillStyle = '#fff'; ctx.textAlign = 'right';
    ctx.fillText(brl(totalGeral), W - PAD - 10, totMid + 12);
    ctx.textAlign = 'left';

    y += TOTAL_H + FOOTER_H * 0.4;

    // ── Rodapé ────────────────────────────────────────────────────────────────
    ctx.font = '16px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.24)'; ctx.textAlign = 'center';
    ctx.fillText('Gerado pelo FiadoApp · Os valores são baseados nos registros do sistema.', W / 2, y);
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
  window.gerarRelatorioImagem = function (vendas, meta) {
    meta = meta || {};
    ensureLogo(() => {
      try {
        const canvas = document.createElement('canvas');
        desenhar(canvas, vendas, meta);
        baixar(canvas, 'relatorio_fiadoapp.png');
      } catch (e) {
        console.error('gerarRelatorioImagem:', e);
        if (typeof showToast === 'function') showToast('Erro ao gerar imagem.', 'error');
      }
    });
  };

})();
