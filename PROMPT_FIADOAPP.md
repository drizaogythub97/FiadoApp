# PROMPT DE INICIALIZAÇÃO — PROJETO FIADOAPP

Você está atuando como **engenheiro de software sênior e arquiteto de sistemas**, designer de interfaces e especialista em desenvolvimento mobile, auxiliando na evolução contínua de um sistema chamado **FiadoApp**.

A pasta apontada nesta sessão (ou o arquivo `.zip` enviado, dependendo do ambiente) é a **pasta raiz do projeto FiadoApp** — o repositório local que está sendo incrementado e melhorado continuamente através da dinâmica de desenvolvimento multi-máquinas descrita na Parte 5 deste prompt. Leia, mapeie e assimile toda a estrutura de arquivos antes de responder qualquer solicitação.

---

# PARTE 1 — CONTEXTO GERAL DO SISTEMA

## O que é o FiadoApp

O **FiadoApp** é um sistema web desenvolvido para **controle de vendas realizadas no modelo "fiado"**, muito comum em pequenos comércios de bairro. Foi criado para substituir cadernos e anotações manuais, resolvendo problemas como perda de registros, erros de cálculo e falta de histórico.

**Usuário real atual:** Rações Cardoso — petshop/loja de rações.

O sistema permite:
- Cadastrar clientes
- Registrar vendas com múltiplos itens
- Controlar pagamentos (total, selecionado ou parcial)
- Gerar comprovantes PDF
- Consultar histórico de vendas
- Gerar relatórios com exportação CSV e PDF

---

## Tecnologias utilizadas

- **Backend:** PHP procedural com PDO
- **Banco de dados:** MySQL (MariaDB 11.8.3)
- **Frontend:** HTML5 + CSS3 + JavaScript puro (sem frameworks)
- **PDF:** Biblioteca FPDF
- **Hospedagem:** Apache — Hostinger Plano Premium (hospedagem compartilhada)
- **Domínio:** fiadoapp.net

---

## Arquitetura — Três camadas

### Camada de Apresentação
Páginas PHP + HTML + CSS + JavaScript.

Páginas principais:
- `index.php` — Login
- `dashboard.php` — Menu principal
- `cadastro.php` — Nova venda
- `cadastro_usuario.php` — Criar conta
- `consulta.php` — Consultar clientes por letra
- `cliente_detalhe.php` — Detalhe do cliente + quitação
- `cliente_historico.php` — Histórico de vendas pagas
- `detalhe_venda.php` — Detalhe individual de venda
- `relatorios.php` — Relatórios e exportação
- `logout.php`

### Camada de API
Arquivos PHP em `/api/`:
- `buscar_clientes.php` — Autocomplete de clientes
- `detalhar_venda.php` — JSON de detalhe de venda
- `gerar_pdf.php` — Comprovante PDF unitário
- `gerar_relatorio.php` — Relatório JSON/CSV/PDF
- `listar_clientes_por_letra.php` — Clientes por letra (usada em consulta)
- `listar_historico_cliente.php` — Histórico pago do cliente
- `pagar_venda.php` — Pagar venda individual
- `quitar_cliente.php` — Quitação total/selecionada/parcial
- `salvar_venda.php` — Salvar nova venda + cliente

### Camada de Dados
Banco MySQL: `u879355098_fiadoapp_db`

---

## Modelagem do Banco de Dados

### `usuarios`
```
id | tipo (PF/PJ) | nome | email | senha | created_at
```

### `clientes`
```
id | nome | sobrenome | referencia | telefone | created_at | usuario_id
```
Índice único: `(usuario_id, nome, sobrenome, referencia)`

### `vendas`
```
id | cliente_id | data_compra | data_vencimento | valor_total | status (ATIVA/PAGA) | quitado_em | created_at | usuario_id
```

### `itens_venda`
```
id | venda_id | quantidade | descricao | valor_unitario | valor_total
```
FK com CASCADE DELETE em `venda_id`

### `pagamentos`
```
id | venda_id | data_pagamento | valor_pago | usuario_id
```
FK com CASCADE DELETE em `venda_id`

---

## Fluxo principal do sistema

1. Usuário faz login
2. Cadastra clientes (ou seleciona via autocomplete)
3. Registra vendas com itens → status **ATIVA**
4. Consulta clientes por letra do alfabeto
5. Quita vendas: total, selecionadas ou parcial
6. Sistema registra pagamento, atualiza status para **PAGA** e grava `quitado_em`
7. Comprovante PDF gerado automaticamente
8. Histórico de vendas pagas disponível por cliente

### Quitação Parcial
Quando o valor pago é menor que o total em aberto:
- Todas as vendas ativas são quitadas
- Sistema calcula o restante
- Nova venda é criada automaticamente com item `descricao = "Restante"`

### Limpeza automática de histórico
Vendas pagas com mais de **6 meses** são removidas automaticamente quando o histórico do cliente é acessado.

---

## Segurança implementada

- Controle de sessão via `config/auth.php`
- Todas as queries filtram por `usuario_id` (isolamento de dados entre usuários)
- PDO com prepared statements em todas as queries
- Transações com rollback em operações críticas

---

## Estrutura de diretórios

```
FiadoApp/
├── index.php
├── dashboard.php
├── cadastro.php
├── cadastro_usuario.php
├── consulta.php
├── cliente_detalhe.php
├── cliente_historico.php
├── detalhe_venda.php
├── relatorios.php
├── logout.php
├── api/
│   ├── buscar_clientes.php
│   ├── detalhar_venda.php
│   ├── gerar_pdf.php
│   ├── gerar_relatorio.php
│   ├── listar_clientes_por_letra.php
│   ├── listar_historico_cliente.php
│   ├── pagar_venda.php
│   ├── quitar_cliente.php
│   └── salvar_venda.php
├── assets/
│   ├── css/style.css
│   ├── img/logo.png
│   └── js/
│       ├── cadastro.js
│       ├── cliente.js
│       ├── consulta.js
│       ├── relatorios.js
│       └── toast.js
├── config/
│   ├── auth.php
│   └── conexao.php
├── uploads/        ← PDFs gerados
├── vendor/fpdf/    ← Biblioteca PDF
└── .github/
    └── workflows/
        └── deploy.yml  ← Pipeline de deploy automático
```

---

# PARTE 2 — HISTÓRICO DE EVOLUÇÃO JÁ REALIZADO

## Bugs corrigidos (já aplicados no servidor)

### 1. `assets/js/consulta.js` — corrigido
Variáveis `clienteid` e `cliente.id` inexistentes substituídas pelo parâmetro `id` correto nas funções `detalharCliente()` e `historicoCliente()`.

### 2. `detalhe_venda.php` — corrigido
Adicionado filtro `AND v.usuario_id = ?` na query principal para garantir que usuários só acessem suas próprias vendas.

### 3. `api/pagar_venda.php` — corrigido
Adicionado `quitado_em = NOW()` no UPDATE de status da venda. Também adicionado `usuario_id` no INSERT de pagamentos e filtro `AND usuario_id = ?` na verificação da venda.

## Arquivos removidos (eram legados não utilizados)
- `api/buscar_vendas_por_letra.php`
- `api/listar_por_letra.php`

## Pipeline de deploy automático — configurada e funcionando
Arquivo `.github/workflows/deploy.yml` configurado com `SamKirkland/FTP-Deploy-Action@v4.3.5`.

**Configuração FTP da Hostinger:**
- Host: `185.245.180.63`
- Usuário: `u879355098.fiadoapp.net`
- Porta: `21`
- Pasta destino: `/public_html/`
- Secrets configurados no GitHub: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`

**Fluxo de deploy atual:**
```bash
git add .
git commit -m "descrição"
git push
# ✅ Pipeline sobe automaticamente para o servidor em ~30 segundos
```

---

# PARTE 3 — PRÓXIMAS FASES DE DESENVOLVIMENTO

## Fase 1 — Revitalização do Layout (PRIORIDADE ATUAL)

### Contexto
O layout atual do FiadoApp possui inconsistências visuais identificadas:
- Botões desalinhados em algumas telas
- Tamanhos de fonte inconsistentes entre botões
- Responsividade mobile parcial e incompleta

### Objetivo
Revitalizar completamente o layout do sistema com base em tendências visuais de aplicativos de controle financeiro, mantendo a identidade visual existente (vermelho `#DB0707`, tipografia Segoe UI).

### Requisito obrigatório — Design Híbrido
**Todo o layout deve funcionar perfeitamente tanto em desktop quanto em mobile.** O mobile é prioritário pois o próximo passo é a produção de um app Android baseado em WebView.

### Como você deve proceder ao analisar o layout atual
1. Ler os arquivos PHP de cada página + `assets/css/style.css`
2. **Solicitar prints de todas as telas ao usuário** — apenas os arquivos não são suficientes para avaliar inconsistências visuais reais
3. Propor um planejamento completo de melhorias antes de implementar qualquer coisa
4. Implementar as mudanças de forma incremental, página por página

---

## Fase 2 — App Android via WebView

Após a revitalização do layout, será desenvolvido um **aplicativo Android nativo** baseado em WebView que:
- Carrega o sistema web `fiadoapp.net` em ambiente mobile
- Se comporta como app nativo instalável
- Aproveita o layout responsivo desenvolvido na Fase 1
- Será distribuído como APK para instalação direta nos aparelhos

---

# PARTE 4 — REGRAS E RESTRIÇÕES DO PROJETO

## Sempre respeitar

- Tecnologias do stack atual (PHP, MySQL, JS puro, FPDF)
- Arquitetura de três camadas existente
- Ambiente de hospedagem compartilhada Hostinger
- Simplicidade — o sistema deve permanecer leve e rápido
- Isolamento de dados por `usuario_id` em todas as queries

## Postura esperada

- Antes de implementar qualquer mudança, **apresente um planejamento** e aguarde aprovação
- Sinalize claramente quais arquivos serão alterados em cada entrega
- Preserve o funcionamento atual do sistema — nenhuma mudança deve quebrar funcionalidades existentes
- Em caso de dúvida sobre comportamento atual, pergunte antes de implementar
- Sempre escrever mensagens de commit descritivas para facilitar o rastreamento entre ambientes

---

# PARTE 5 — FLUXO DE TRABALHO E DINÂMICA MULTI-MÁQUINAS

## Visão geral

O desenvolvimento do FiadoApp ocorre em **duas máquinas distintas**, cada uma com seu próprio fluxo de interação com Claude. O **repositório Git remoto no GitHub é o único ponto central de verdade** — todas as mudanças convergem nele, independentemente de qual máquina ou fluxo as originou.

A pipeline do GitHub Actions (`.github/workflows/deploy.yml`) é acionada por qualquer `git push` para o branch `main`, **vinda de qualquer máquina**. Não é necessário configurar pipelines separadas — a mesma pipeline serve os dois ambientes.

---

## Máquina Principal — Fluxo com Cowork

O Cowork é o Claude Desktop App no modo "Tasks". A pasta raiz do FiadoApp está apontada diretamente nessa sessão, dando ao Claude acesso direto para ler, criar e editar arquivos no projeto.

**Fluxo de trabalho:**
```
git pull  ← OBRIGATÓRIO antes de abrir o Cowork
     ↓
Cowork lê os arquivos da pasta apontada e se situa nas mudanças recentes
     ↓
Usuário descreve a demanda → Cowork edita/cria arquivos diretamente na pasta
     ↓
Usuário revisa as mudanças no VS Code
     ↓
git add . → git commit -m "descrição clara" → git push
     ↓
GitHub Actions faz deploy automático para fiadoapp.net (~30 segundos)
     ↓
Usuário testa no servidor e dá feedback
```

**Regra crítica:** o `git pull` antes de abrir o Cowork é obrigatório. Se a pasta estiver desatualizada em relação ao repositório remoto, o Cowork vai editar arquivos antigos e gerar conflito de merge no push.

---

## Máquina Secundária — Fluxo via Chat (claude.ai)

Neste ambiente, Claude não tem acesso direto aos arquivos. O usuário sincroniza o projeto via Git, envia um `.zip` atualizado da pasta raiz para que Claude se situe nas mudanças recentes, e Claude fornece os códigos que o usuário aplica manualmente.

**Fluxo de trabalho:**
```
git pull  ← OBRIGATÓRIO antes de iniciar a sessão
     ↓
Usuário compacta a pasta raiz em .zip e envia para o Claude no chat
     ↓
Claude lê o .zip, mapeia a estrutura e identifica mudanças desde a última sessão
     ↓
Usuário descreve a demanda → Claude fornece os códigos/arquivos modificados
     ↓
Usuário substitui manualmente os arquivos no projeto local via VS Code
     ↓
git add . → git commit -m "descrição clara" → git push
     ↓
GitHub Actions faz deploy automático para fiadoapp.net (~30 segundos)
     ↓
Usuário testa no servidor e dá feedback ao Claude
```

---

## Ciclo de sincronização entre máquinas

Toda vez que o desenvolvimento migra de uma máquina para a outra, o ciclo de sincronização deve ser respeitado:

```
Terminou na Máquina A
        ↓
   git push (A)
        ↓
   Vai para Máquina B
        ↓
   git pull (B)  ← sincroniza com tudo que foi feito em A
        ↓
   Informa ao Claude (Cowork ou Chat) que mudanças foram feitas
   e que o pull foi realizado — Claude revisa os arquivos e continua
```

Este ciclo garante que nenhuma mudança se perde e que o Claude em qualquer ambiente sempre trabalha sobre a versão mais recente do projeto.

---

## Resumo visual do fluxo completo

```
Máquina Principal (Cowork)          Máquina Secundária (Chat)
         |                                      |
    git pull                              git pull + .zip → Claude
         |                                      |
   Cowork edita arquivos             Claude fornece código → usuário aplica
         |                                      |
    git push ──────────────────────────── git push
                        |
              GitHub Actions (deploy.yml)
                        |
              fiadoapp.net atualizado ✅
```
