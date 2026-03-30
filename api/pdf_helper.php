<?php
/**
 * FiadoApp — PDF Helper
 * Centraliza classes, estilos e utilitários compartilhados entre todos os documentos PDF.
 *
 * Classes disponíveis (requerem fpdf.php já carregado):
 *   ComprovantePDF  — comprovante de pagamento individual (A4 portrait)
 *   QuitarPDF       — comprovante de quitação (múltiplas vendas, A4 portrait)
 *   RelatorioPDF    — relatório de vendas (A4 landscape)
 */

// ── Utilitários ─────────────────────────────────────────────────────────────

if (!function_exists('enc')) {
    function enc($s) { return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s ?? ''); }
}

if (!function_exists('brl')) {
    function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

// ── Classe base (A4 portrait) ────────────────────────────────────────────────

class FiadoBasePDF extends FPDF {

    /** Tipo do documento exibido no cabeçalho direito */
    public $docType = 'DOCUMENTO';

    function Header() {
        // Barra coral topo
        $this->SetFillColor(232, 98, 74);
        $this->Rect(0, 0, 210, 7, 'F');

        // Logo
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)) $this->Image($logoPath, 12, 12, 14);

        // Marca — esquerda
        $this->SetXY(29, 11);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(232, 98, 74);
        $this->Cell(80, 6, enc('FiadoApp'), 0, 0, 'L');

        $this->SetXY(29, 17);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(80, 5, enc('Controle de vendas a prazo'), 0, 0, 'L');

        // Tipo do documento — direita
        $this->SetXY(120, 11);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(0, 5, enc($this->docType), 0, 1, 'R');

        $this->SetXY(120, 17);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(140, 140, 160);
        $this->Cell(0, 5, enc('Emitido em: ' . date('d/m/Y H:i')), 0, 0, 'R');

        // Linha coral divisória
        $this->SetDrawColor(232, 98, 74);
        $this->SetLineWidth(0.4);
        $this->Line(12, 30, 198, 30);
        $this->SetY(35);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetDrawColor(215, 215, 225);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 165);
        $this->Cell(0, 4, enc('Documento gerado automaticamente  •  FiadoApp  •  fiadoapp.net'), 0, 0, 'C');
    }

    /**
     * Faixa com título de seção (fundo coral, texto branco).
     */
    function SectionTitle($title) {
        $this->SetFillColor(232, 98, 74);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(0, 6, enc('  ' . strtoupper($title)), 0, 1, 'L', true);
        $this->Ln(1);
    }

    /**
     * Linha de informação com borda coral lateral e alternância de fundo.
     */
    function InfoCard($label, $value, $fill = false) {
        $startX = $this->GetX();
        $startY = $this->GetY();
        $h = 8;

        if ($fill) $this->SetFillColor(245, 245, 248);
        else       $this->SetFillColor(255, 255, 255);
        $this->Rect($startX, $startY, 186, $h, 'F');

        // Borda coral 3px à esquerda
        $this->SetFillColor(232, 98, 74);
        $this->Rect($startX, $startY, 3, $h, 'F');

        $this->SetXY($startX + 6, $startY);
        $this->SetFont('Arial', 'B', 7.5);
        $this->SetTextColor(110, 110, 130);
        $this->Cell(48, $h, enc(strtoupper($label)), 0, 0, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(0, $h, enc($value), 0, 1, 'L');
    }

    /**
     * Caixa coral de destaque com label e valor monetário grande.
     * Usada ao final dos comprovantes.
     */
    function ValorDestaque($label, $valor) {
        $boxY = $this->GetY();
        $this->SetFillColor(232, 98, 74);
        $this->Rect(12, $boxY, 186, 26, 'F');

        $this->SetXY(12, $boxY + 4);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', '', 8.5);
        $this->Cell(186, 6, enc(strtoupper($label)), 0, 1, 'C');

        $this->SetFont('Arial', 'B', 22);
        $this->Cell(186, 14, enc(brl($valor)), 0, 1, 'C');
        $this->Ln(7);
    }

    /**
     * Caixa de destaque para o saldo restante em aberto após pagamento parcial.
     */
    function SaldoRestante($valor) {
        $boxY = $this->GetY();
        // Borda e fundo amarelo-alaranjado claro para contrastar com o vermelho do total
        $this->SetFillColor(255, 243, 220);
        $this->Rect(12, $boxY, 186, 22, 'F');

        // Borda sutil
        $this->SetDrawColor(230, 160, 60);
        $this->Rect(12, $boxY, 186, 22, 'D');

        $this->SetXY(12, $boxY + 3);
        $this->SetTextColor(160, 90, 10);
        $this->SetFont('Arial', '', 8);
        $this->Cell(186, 5, enc('SALDO RESTANTE EM ABERTO'), 0, 1, 'C');

        $this->SetFont('Arial', 'B', 16);
        $this->Cell(186, 11, enc(brl($valor)), 0, 1, 'C');

        // Restaura cor de desenho
        $this->SetDrawColor(0, 0, 0);
        $this->Ln(6);
    }

    /**
     * Nota de rodapé textual ao final do documento.
     */
    function NotaFinal($texto) {
        $this->SetFont('Arial', 'I', 7.5);
        $this->SetTextColor(150, 150, 165);
        $this->Cell(0, 5, enc($texto), 0, 1, 'C');
    }
}

// ── Comprovante de Pagamento Individual ─────────────────────────────────────

class ComprovantePDF extends FiadoBasePDF {

    public $docType = 'COMPROVANTE DE PAGAMENTO';

    /** Cabeçalho da tabela de itens da venda. */
    function ItemHeader() {
        $this->SetFillColor(30, 30, 45);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(93, 7, enc('DESCRICAO'),  0, 0, 'L', true);
        $this->Cell(23, 7, enc('QTD'),        0, 0, 'C', true);
        $this->Cell(35, 7, enc('VL. UNIT.'),  0, 0, 'R', true);
        $this->Cell(35, 7, enc('SUBTOTAL'),   0, 1, 'R', true);
    }

    /** Linha de item da venda com alternância de fundo. */
    function ItemRow($desc, $qtd, $unit, $total, $fill = false) {
        if ($fill) $this->SetFillColor(245, 245, 248);
        else       $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 8.5);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(93, 7, enc($desc),        0, 0, 'L', true);
        $this->Cell(23, 7, enc($qtd . 'x'),   0, 0, 'C', true);
        $this->Cell(35, 7, enc(brl($unit)),   0, 0, 'R', true);
        $this->Cell(35, 7, enc(brl($total)),  0, 1, 'R', true);
    }

    /** Linha de total da tabela de itens (fundo escuro). */
    function ItemTotal($valorTotal) {
        $this->SetFillColor(30, 30, 45);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.5);
        $this->Cell(151, 8, enc('TOTAL'), 0, 0, 'R', true);
        $this->Cell(35,  8, enc(brl($valorTotal)), 0, 1, 'R', true);
        $this->Ln(5);
    }
}

// ── Comprovante de Quitação (múltiplas vendas) ───────────────────────────────

class QuitarPDF extends FiadoBasePDF {

    public $docType = 'COMPROVANTE DE QUITACAO';

    /** Cabeçalho da tabela de vendas quitadas. */
    function VendaTableHeader() {
        $this->SetFillColor(30, 30, 45);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(20, 7, enc('Venda'),       0, 0, 'C', true);
        $this->Cell(35, 7, enc('Data Compra'), 0, 0, 'C', true);
        $this->Cell(93, 7, enc('Itens'),       0, 0, 'L', true);
        $this->Cell(38, 7, enc('Valor'),       0, 1, 'R', true);
    }

    /** Linha de venda quitada com alternância de fundo. */
    function VendaRow($id, $data, $itensStr, $valor, $fill = false) {
        if ($fill) $this->SetFillColor(245, 245, 248);
        else       $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(30, 30, 45);
        $this->SetFont('Arial', '', 8);
        $this->Cell(20, 7, enc('#' . str_pad($id, 4, '0', STR_PAD_LEFT)), 0, 0, 'C', true);
        $this->Cell(35, 7, enc($data),                                     0, 0, 'C', true);
        $this->Cell(93, 7, enc(mb_substr($itensStr, 0, 55)),               0, 0, 'L', true);
        $this->Cell(38, 7, enc(brl($valor)),                               0, 1, 'R', true);
    }

    /** Linha de total das vendas quitadas (fundo escuro). */
    function VendaTotal($totalQuitado) {
        $this->SetFillColor(30, 30, 45);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8.5);
        $this->Cell(148, 8, enc('TOTAL QUITADO'), 0, 0, 'R', true);
        $this->Cell(38,  8, enc(brl($totalQuitado)), 0, 1, 'R', true);
        $this->Ln(5);
    }
}

// ── Relatório de Vendas (A4 landscape) ──────────────────────────────────────

class RelatorioPDF extends FiadoBasePDF {

    public $usuario_nome = '';
    public $periodo      = '';
    public $totalGeral   = 0;
    public $totalPago    = 0;
    public $totalAtivo   = 0;
    public $qtd          = 0;

    /** Override: cabeçalho landscape (297mm de largura). */
    function Header() {
        // Barra coral topo
        $this->SetFillColor(232, 98, 74);
        $this->Rect(0, 0, 297, 7, 'F');

        // Logo
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)) $this->Image($logoPath, 12, 12, 14);

        // Marca — esquerda
        $this->SetXY(29, 11);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(232, 98, 74);
        $this->Cell(100, 7, enc('FiadoApp'), 0, 0, 'L');

        $this->SetXY(29, 18);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(100, 5, enc('Relatorio de Vendas'), 0, 0, 'L');

        // Info — direita
        $this->SetXY(180, 11);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(30, 30, 45);
        $this->Cell(0, 5, enc('Gerado por: ' . $this->usuario_nome), 0, 1, 'R');

        $this->SetXY(180, 17);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(100, 100, 120);
        if ($this->periodo) $this->Cell(0, 5, enc($this->periodo), 0, 1, 'R');

        $this->SetXY(180, 22);
        $this->Cell(0, 5, enc('Emitido em: ' . date('d/m/Y H:i')), 0, 1, 'R');

        // Linha coral divisória
        $this->SetDrawColor(232, 98, 74);
        $this->SetLineWidth(0.4);
        $this->Line(12, 30, 285, 30);
        $this->SetY(35);
    }

    /** Override: rodapé landscape com número de páginas. */
    function Footer() {
        $this->SetY(-14);
        $this->SetDrawColor(215, 215, 225);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 285, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(150, 150, 165);
        $this->Cell(0, 4, enc('FiadoApp  -  fiadoapp.net'), 0, 0, 'L');
        $this->Cell(0, 4, enc('Pagina ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }

    /** Cabeçalho das colunas da tabela de vendas. */
    function TableHeader($cols) {
        $this->SetFillColor(232, 98, 74);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $headers = ['#', enc('Cliente'), enc('Compra'), enc('Vencimento'), enc('Valor'), enc('Status')];
        foreach ($headers as $i => $h) {
            $align = ($i === 4) ? 'R' : 'L';
            $this->Cell($cols[$i], 7, $h, 0, 0, $align, true);
        }
        $this->Ln();
    }

    /** Retorna a cor RGB do status da venda. */
    function StatusColor($status) {
        switch ($status) {
            case 'PAGA':    return [31, 168, 90];
            case 'ATIVA':   return [232, 98, 74];
            case 'PARCIAL': return [245, 158, 11];
            default:        return [100, 100, 120];
        }
    }

    /** Card de resumo com borda colorida à esquerda. */
    function SummaryCard($cx, $y, $label, $value, $color) {
        $cardW = 60;
        $cardH = 20;
        $this->SetFillColor(245, 245, 248);
        $this->Rect($cx, $y, $cardW, $cardH, 'F');
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->Rect($cx, $y, 3, $cardH, 'F');

        $this->SetXY($cx + 5, $y + 3);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(110, 110, 130);
        $this->Cell($cardW - 5, 5, enc(strtoupper($label)), 0, 1, 'L');

        $this->SetXY($cx + 5, $this->GetY());
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor($color[0], $color[1], $color[2]);
        $this->Cell($cardW - 5, 7, enc($value), 0, 0, 'L');
    }
}
