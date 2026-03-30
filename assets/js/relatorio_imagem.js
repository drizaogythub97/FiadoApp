/**
 * relatorio_imagem.js
 * Geração client-side de relatório de vendas em PNG via Canvas.
 * Largura fixa: 1080px. Altura dinâmica baseada nos registros.
 *
 * API pública:
 *   gerarRelatorioImagem(vendas, meta)
 *   - vendas  [{id, nome, sobrenome, referencia, data_compra, data_vencimento,
 *               valor_total, status, itens:[{descricao, quantidade,
 *               valor_unitario, valor_total}]}]
 *   - meta    { emitidoPor, filtroStatus, periodo }
 */

(function() {

  // ── Constantes de layout ────────────────────────────────────────────────────
  const W            = 1080;
  const PAD          = 72;
  const COL          = W - PAD * 2;
  const HEADER_H     = 310;
  const BODY_TOP     = 60;
  const SUMMARY_H    = 220;
  const SUMMARY_GAP  = 50;
  const SEC_TITLE_H  = 72;
  const VENDA_HEAD_H = 116;
  const ITEM_H       = 82;
  const VENDA_FOOT_H = 92;
  const VENDA_SEP    = 36;
  const TOTAL_H      = 110;
  const FOOTER_H     = 100;
  const BODY_BOTTOM  = 50;

  const STATUS_COR = { ATIVA: '#f0a030', PAGA: '#3ec98a' };

  function brl(v) {
    return 'R$ ' + parseFloat(v).toLocaleString('pt-BR',
      { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function fmtData(iso) {
    if (!iso) return '—';
    const p = iso.split(' ')[0].split('-');
    return p.length < 3 ? iso : `${p[2]}/${p[1]}/${p[0]}`;
  }

  function calcHeight(vendas) {
    let vendasH = 0;
    vendas.forEach(v => {
      const n = (v.itens || []).length || 1;
      vendasH += VENDA_HEAD_H + n * ITEM_H + VENDA_FOOT_H + VENDA_SEP;
    });
    return HEADER_H + BODY_TOP + SUMMARY_H + SUMMARY_GAP + SEC_TITLE_H
         + vendasH + TOTAL_H + FOOTER_H + BODY_BOTTOM;
  }

  function desenhar(canvas, vendas, meta) {
    const H = calcHeight(vendas);
    canvas.width  = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, W, H);

    const totalGeral  = vendas.reduce((s, v) => s + parseFloat(v.valor_total), 0);
    const totalAtivas = vendas.filter(v => v.status === 'ATIVA').reduce((s, v) => s + parseFloat(v.valor_total), 0);
    const totalPagas  = vendas.filter(v => v.status === 'PAGA').reduce((s, v) => s + parseFloat(v.valor_total), 0);

    // Background
    ctx.fillStyle = '#13131f';
    ctx.fillRect(0, 0, W, H);

    // Listra lateral coral
    const stripeGrad = ctx.createLinearGradient(0, 0, 0, H * 0.55);
    stripeGrad.addColorStop(0, '#e8624a');
    stripeGrad.addColorStop(1, 'rgba(232,98,74,0)');
    ctx.fillStyle = stripeGrad;
    ctx.fillRect(0, 0, 8, H);

    // ── Cabeçalho ──
    const hGrad = ctx.createLinearGradient(0, 0, W, HEADER_H);
    hGrad.addColorStop(0, '#e8624a'); hGrad.addColorStop(1, '#b83628');
    ctx.fillStyle = hGrad;
    ctx.beginPath();
    ctx.moveTo(0, 0); ctx.lineTo(W, 0);
    ctx.lineTo(W, HEADER_H - 55);
    ctx.quadraticCurveTo(W, HEADER_H, W - 90, HEADER_H);
    ctx.lineTo(90, HEADER_H);
    ctx.quadraticCurveTo(0, HEADER_H, 0, HEADER_H - 55);
    ctx.closePath();
    ctx.fill();

    // Logo
    const logoSz = 86;
    ctx.save();
    ctx.beginPath();
    ctx.arc(PAD + logoSz / 2, 90, logoSz / 2, 0, Math.PI * 2);
    ctx.clip();
    const logo = window._fiadoLogo;
    if (logo && logo.complete && logo.naturalWidth > 0) {
      ctx.drawImage(logo, PAD, 47, logoSz, logoSz);
    } else {
      ctx.fillStyle = 'rgba(255,255,255,0.25)'; ctx.fill();
      ctx.fillStyle = '#fff'; ctx.font = 'bold 52px Arial';
      ctx.textAlign = 'center'; ctx.fillText('F', PAD + logoSz / 2, 108);
    }
    ctx.restore();

    ctx.textAlign = 'left'; ctx.fillStyle = '#fff';
    ctx.font = 'bold 54px Arial';
    ctx.fillText('FiadoApp', PAD + logoSz + 22, 82);

    ctx.font = 'bold 38px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.92)';
    ctx.fillText('Relatório de Vendas', PAD + logoSz + 22, 134);

    ctx.font = '28px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.58)';
    ctx.fillText(`Emitido em ${meta.dataEmissao || ''} · por ${meta.emitidoPor || ''}`, PAD + logoSz + 22, 178);

    ctx.textAlign = 'right'; ctx.font = '26px Arial';
    ctx.fillStyle = 'rgba(255,255,255,0.5)';
    if (meta.periodo) ctx.fillText(meta.periodo, W - PAD, 236);
    const filtroLabel = { '': 'Todas as vendas', ATIVA: 'Somente ativas', PAGA: 'Somente pagas' }[meta.filtroStatus || ''] || 'Todas as vendas';
    ctx.fillText(filtroLabel, W - PAD, 266);

    // ── Cards de resumo ──
    let y = HEADER_H + BODY_TOP;
    const terceiroLabel = (meta.filtroStatus === 'PAGA') ? 'Total Pago' : 'A Receber';
    const terceiroVal   = (meta.filtroStatus === 'PAGA') ? totalPagas    : totalAtivas;

    const cards = [
      { label: 'Registros',   value: String(vendas.length), cor: '#4a9eff' },
      { label: 'Total Geral', value: brl(totalGeral),        cor: '#e8624a' },
      { label: terceiroLabel, value: brl(terceiroVal),       cor: '#3ec98a' },
    ];
    const cardW = Math.floor((COL - 24) / 3);
    const cardH = SUMMARY_H - 20;

    cards.forEach((c, i) => {
      const cx = PAD + i * (cardW + 12);
      ctx.fillStyle = 'rgba(255,255,255,0.04)';
      ctx.beginPath(); ctx.roundRect(cx, y, cardW, cardH, 14); ctx.fill();
      ctx.strokeStyle = c.cor; ctx.lineWidth = 2;
      ctx.beginPath(); ctx.roundRect(cx, y, cardW, cardH, 14); ctx.stroke();
      ctx.fillStyle = c.cor;
      ctx.beginPath(); ctx.roundRect(cx, y, cardW, 6, [3,3,0,0]); ctx.fill();
      ctx.textAlign = 'center';
      ctx.font = '26px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.5)';
      ctx.fillText(c.label, cx + cardW / 2, y + 60);
      ctx.font = 'bold 34px Arial'; ctx.fillStyle = c.cor;
      let txt = c.value;
      while (ctx.measureText(txt).width > cardW - 16 && txt.length > 4) txt = txt.slice(0,-4)+'…';
      ctx.fillText(txt, cx + cardW / 2, y + 110);
      ctx.textAlign = 'left';
    });

    y += SUMMARY_H + SUMMARY_GAP;

    // ── Título seção ──
    ctx.font = 'bold 30px Arial'; ctx.fillStyle = '#e8624a';
    ctx.fillText('VENDAS', PAD, y);
    ctx.strokeStyle = '#e8624a'; ctx.lineWidth = 2; ctx.globalAlpha = 0.3;
    ctx.beginPath(); ctx.moveTo(PAD, y + 12); ctx.lineTo(W - PAD, y + 12); ctx.stroke();
    ctx.globalAlpha = 1;
    y += SEC_TITLE_H;

    // ── Registros de venda ──
    vendas.forEach((v, vi) => {
      const itens  = v.itens || [];
      const sc     = STATUS_COR[v.status] || '#888';
      const blockH = VENDA_HEAD_H + itens.length * ITEM_H + VENDA_FOOT_H;

      // Fundo bloco
      ctx.fillStyle = vi % 2 === 0 ? 'rgba(255,255,255,0.03)' : 'rgba(255,255,255,0.07)';
      ctx.beginPath(); ctx.roundRect(PAD - 26, y - 8, COL + 52, blockH + 16, 14); ctx.fill();

      // Borda esquerda colorida por status
      ctx.fillStyle = sc;
      ctx.beginPath(); ctx.roundRect(PAD - 26, y - 8, 6, blockH + 16, [3,0,0,3]); ctx.fill();

      // ── Cabeçalho da venda ──
      ctx.font = 'bold 32px Arial'; ctx.fillStyle = '#e8624a'; ctx.textAlign = 'left';
      ctx.fillText(`#${v.id}`, PAD + 12, y + 36);

      // Badge status
      const badgeW = 148, badgeH = 40;
      ctx.fillStyle = sc + '22'; ctx.strokeStyle = sc; ctx.lineWidth = 1.5;
      ctx.beginPath(); ctx.roundRect(PAD + 12 + 92, y + 10, badgeW, badgeH, 8); ctx.fill(); ctx.stroke();
      ctx.font = 'bold 24px Arial'; ctx.fillStyle = sc; ctx.textAlign = 'center';
      ctx.fillText(v.status, PAD + 12 + 92 + badgeW / 2, y + 38);
      ctx.textAlign = 'left';

      // Cliente
      const nome    = `${v.nome || ''}${v.sobrenome ? ' ' + v.sobrenome : ''}`;
      const nomeRef = v.referencia ? `${nome}  (${v.referencia})` : nome;
      ctx.font = 'bold 34px Arial'; ctx.fillStyle = '#fff';
      ctx.fillText(nomeRef, PAD + 12, y + 84);

      // Datas
      ctx.textAlign = 'right'; ctx.font = '26px Arial';
      ctx.fillStyle = 'rgba(255,255,255,0.48)';
      ctx.fillText(`Compra: ${fmtData(v.data_compra)}`, W - PAD - 12, y + 34);
      ctx.fillText(`Vence: ${fmtData(v.data_vencimento)}`, W - PAD - 12, y + 68);
      ctx.textAlign = 'left';

      y += VENDA_HEAD_H;

      // ── Itens ──
      itens.forEach((it, ii) => {
        ctx.fillStyle = ii % 2 === 0 ? 'rgba(0,0,0,0.14)' : 'rgba(0,0,0,0.07)';
        ctx.fillRect(PAD - 20, y, COL + 40, ITEM_H);

        // Separador
        ctx.strokeStyle = 'rgba(255,255,255,0.06)'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(PAD - 20, y); ctx.lineTo(W - PAD + 20, y); ctx.stroke();

        const midY = y + ITEM_H / 2;

        ctx.font = '28px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.35)';
        ctx.textAlign = 'left'; ctx.fillText('›', PAD + 16, midY + 10);

        ctx.font = '30px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.85)';
        ctx.fillText(it.descricao || it.desc || '', PAD + 48, midY + 10);

        ctx.textAlign = 'right';
        ctx.font = '24px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.46)';
        const unit = parseFloat(it.valor_unitario || it.unit || 0);
        ctx.fillText(`${it.quantidade || it.qtd || 1}x  ×  R$ ${unit.toFixed(2).replace('.', ',')}`, W - PAD - 12, midY - 6);

        ctx.font = 'bold 32px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.9)';
        ctx.fillText(brl(it.valor_total || it.sub || 0), W - PAD - 12, midY + 26);
        ctx.textAlign = 'left';

        y += ITEM_H;
      });

      // ── Rodapé da venda ──
      ctx.fillStyle = 'rgba(232,98,74,0.10)';
      ctx.fillRect(PAD - 20, y, COL + 40, VENDA_FOOT_H);

      ctx.strokeStyle = 'rgba(232,98,74,0.25)'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(PAD - 20, y); ctx.lineTo(W - PAD + 20, y); ctx.stroke();

      const footMid = y + VENDA_FOOT_H / 2;
      ctx.font = 'bold 30px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.55)'; ctx.textAlign = 'left';
      ctx.fillText(`Total da venda  (${itens.length} iten${itens.length !== 1 ? 's' : ''})`, PAD + 12, footMid + 12);

      ctx.font = 'bold 44px Arial'; ctx.fillStyle = '#e8624a'; ctx.textAlign = 'right';
      ctx.fillText(brl(v.valor_total), W - PAD - 12, footMid + 14);
      ctx.textAlign = 'left';

      y += VENDA_FOOT_H + VENDA_SEP;
    });

    // ── Total geral ──
    y += 4;
    const tGrad = ctx.createLinearGradient(PAD - 26, y, W - PAD + 26, y);
    tGrad.addColorStop(0, '#e8624a'); tGrad.addColorStop(1, '#c84336');
    ctx.fillStyle = tGrad;
    ctx.beginPath(); ctx.roundRect(PAD - 26, y, COL + 52, TOTAL_H, 12); ctx.fill();

    const totMid = y + TOTAL_H / 2;
    ctx.font = 'bold 32px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.82)'; ctx.textAlign = 'left';
    ctx.fillText(`TOTAL GERAL  (${vendas.length} venda${vendas.length !== 1 ? 's' : ''})`, PAD + 12, totMid + 14);

    ctx.font = 'bold 48px Arial'; ctx.fillStyle = '#fff'; ctx.textAlign = 'right';
    ctx.fillText(brl(totalGeral), W - PAD - 12, totMid + 16);
    ctx.textAlign = 'left';

    y += TOTAL_H + FOOTER_H * 0.4;

    // ── Rodapé ──
    ctx.font = '24px Arial'; ctx.fillStyle = 'rgba(255,255,255,0.24)'; ctx.textAlign = 'center';
    ctx.fillText('Gerado pelo FiadoApp · Os valores são baseados nos registros do sistema.', W / 2, y);
  }

  // ── Garante logo ────────────────────────────────────────────────────────────
  function ensureLogo(callback) {
    if (window._fiadoLogo !== undefined) { callback(); return; }
    const img = new Image();
    img.onload  = () => { window._fiadoLogo = img; callback(); };
    img.onerror = () => { window._fiadoLogo = null; callback(); };
    img.src = '/assets/img/logo.png';
  }

  // ── Função pública ──────────────────────────────────────────────────────────
  window.gerarRelatorioImagem = function(vendas, meta) {
    meta = meta || {};
    ensureLogo(() => {
      const canvas = document.createElement('canvas');
      desenhar(canvas, vendas, meta);
      const link = document.createElement('a');
      link.download = 'relatorio_fiadoapp.png';
      link.href     = canvas.toDataURL('image/png');
      link.click();
    });
  };

})();
