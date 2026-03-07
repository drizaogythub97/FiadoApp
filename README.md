# FiadoApp

> Sistema web de controle de vendas a prazo ("fiado") para pequenos negócios, com app Android nativo.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Android](https://img.shields.io/badge/Android-minSdk%2024-3DDC84?style=flat-square&logo=android&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-E8624A?style=flat-square)

---

## Sobre o Projeto

O FiadoApp nasceu para resolver um problema real de pequenos comerciantes: controlar quem deve, quanto deve e há quanto tempo, sem precisar de cadernos, planilhas ou sistemas complexos.

A aplicação permite cadastrar clientes, registrar vendas a prazo (com ou sem itens detalhados), acompanhar pagamentos parciais, visualizar inadimplentes e emitir comprovantes em PDF — tudo num sistema web responsivo com tema escuro, acessível pelo navegador ou pelo app Android dedicado.

---

## Funcionalidades

- **Autenticação** com sessão persistente de 30 dias (PHP + bcrypt)
- **Dashboard** com métricas em tempo real: total a receber, vendas ativas, inadimplentes e total de clientes
- **Cadastro de clientes** com suporte a Pessoa Física e Jurídica
- **Registro de vendas a prazo** com data de vencimento e itens detalhados
- **Consulta de clientes** com busca por nome, filtro por situação (com/sem débito) e navegação por inicial
- **Histórico completo** por cliente com todas as vendas e status
- **Quitação de vendas** — total, selecionada ou pagamento parcial — via modal customizado (sem popups do browser)
- **Tela de inadimplentes** com ordenação por dias de atraso e total devido por cliente
- **Geração de PDF** como comprovante de pagamento
- **App Android WebView** com sessão persistente, pull-to-refresh, tela offline e splash screen

---

## Tecnologias

| Camada | Tecnologia |
|--------|-----------|
| Back-end | PHP 8.x (sem framework) |
| Banco de dados | MySQL 8.x via PDO |
| Front-end | HTML5, CSS3 custom (variáveis CSS), JavaScript vanilla |
| App mobile | Android (Java), WebView, AGP 8.3.2, Gradle 8.4 |
| Hospedagem | Hostinger (compatível com qualquer servidor LAMP) |

---

## Estrutura do Projeto

```
FiadoApp/
├── android/                    # Projeto Android Studio (WebView APK)
│   └── app/src/main/
│       └── java/net/fiadoapp/app/
│           ├── MainActivity.java
│           └── SplashActivity.java
│
├── api/                        # Endpoints JSON consumidos pelo front-end
│   ├── buscar_clientes.php
│   ├── dashboard_stats.php
│   ├── detalhar_venda.php
│   ├── gerar_pdf.php
│   ├── gerar_relatorio.php
│   ├── listar_clientes_por_letra.php
│   ├── listar_historico_cliente.php
│   ├── pagar_venda.php
│   ├── quitar_cliente.php
│   └── salvar_venda.php
│
├── assets/
│   ├── css/style.css           # Design system completo (tema escuro, coral #E8624A)
│   ├── js/
│   │   ├── cliente.js          # Modal customizado + lógica de quitação
│   │   └── consulta.js         # Busca, filtros e navegação alfabética
│   └── img/logo.png
│
├── config/
│   ├── auth.php                # Guard de autenticação (incluso em todas as páginas protegidas)
│   ├── conexao.php             # Conexão PDO com o banco
│   └── session.php             # Configuração de sessão persistente (30 dias)
│
├── cadastro.php                # Cadastro de clientes
├── cadastro_usuario.php        # Criação de conta de usuário
├── cliente_detalhe.php         # Detalhe do cliente com vendas ativas
├── cliente_historico.php       # Histórico completo de vendas do cliente
├── consulta.php                # Lista e busca de clientes
├── dashboard.php               # Painel principal com métricas
├── detalhe_venda.php           # Detalhe e quitação de uma venda
├── inadimplentes.php           # Lista de clientes em atraso
├── index.php                   # Login
├── logout.php                  # Encerramento de sessão
└── relatorios.php              # Relatórios financeiros
```

---

## Instalação

### Pré-requisitos

- PHP 8.0+
- MySQL 8.0+
- Servidor web com suporte a `.htaccess` (Apache/LiteSpeed)

### Passo a passo

**1. Clone o repositório**

```bash
git clone https://github.com/drizaogythub97/FiadoApp.git
cd FiadoApp
```

**2. Configure a conexão com o banco**

Edite `config/conexao.php` com suas credenciais:

```php
$host = 'localhost';
$db   = 'fiadoapp_db';
$user = 'seu_usuario';
$pass = 'sua_senha';
```

**3. Importe a estrutura do banco**

Execute o SQL de criação das tabelas no seu MySQL. O esquema contém as tabelas:
`usuarios`, `clientes`, `vendas`, `itens_venda` e `pagamentos`.

**4. Acesse o sistema**

Abra o domínio configurado no servidor. O sistema redireciona automaticamente para a tela de login.

---

## App Android

O app é um wrapper WebView nativo que aponta para a URL do sistema hospedado.

### Compilar o APK

1. Abra a pasta `android/` no Android Studio
2. Aguarde o Gradle sincronizar as dependências
3. Em `MainActivity.java`, confirme que `APP_URL` aponta para o seu domínio:
   ```java
   private static final String APP_URL = "https://seudominio.com";
   ```
4. **Build → Generate Signed APK** (ou `Build → Build Bundle(s)/APK(s)` para debug)

### Requisitos

| Item | Versão |
|------|--------|
| Android mínimo | 7.0 (API 24) |
| Target SDK | 34 (Android 14) |
| Gradle | 8.4 |
| Android Gradle Plugin | 8.3.2 |

### Funcionalidades do app

- Sessão persistente via `CookieManager.flush()` em `onPause` e `onStop`
- Tela offline com botão de nova tentativa
- Pull-to-refresh
- Splash screen com logo
- Botão voltar navega dentro do histórico do WebView

---

## Design System

O sistema usa um design system próprio baseado em CSS custom properties com tema escuro.

**Paleta principal:**

| Variável | Valor | Uso |
|----------|-------|-----|
| `--brand` | `#E8624A` | Coral — cor primária, botões, destaques |
| `--bg-page` | `#13131A` | Fundo da página |
| `--bg-surface-1` | `#19192A` | Cards e superfícies |
| `--bg-surface-2` | `#1E1E2E` | Superfícies secundárias |
| `--text-primary` | `#F0F0F8` | Texto principal |
| `--success` | `#1FA85A` | Vendas pagas / situação positiva |
| `--warning-text` | `#F59E0B` | Avisos e alertas |

---

## Segurança

- Senhas armazenadas com `password_hash()` (bcrypt, PHP_DEFAULT)
- Sessões PHP com cookies `httponly` e `secure` (HTTPS only)
- Proteção contra CSRF via `SameSite=Lax` nos cookies
- Guard de autenticação em todas as rotas protegidas (`config/auth.php`)
- Queries parametrizadas via PDO em toda a camada de dados
- Logout expira o cookie no cliente além de destruir a sessão no servidor

---

## Roadmap

Funcionalidades planejadas para versões futuras:

- [ ] **SaaS multi-tenant** — isolamento de dados por `usuario_id` já implementado na v1, pronto para evolução
- [ ] **Notificações push** — alertas de vencimento via Firebase (Android)
- [ ] **Painel admin** — gerenciamento de contas (`tipo = 'admin'` já mapeado no banco)
- [ ] **Backup automático** — exportação do banco por conta
- [ ] **App para Sistema IOS** — Criação de versão do aplicativo FiadoApp para sistema IOS

---

## Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

---

Desenvolvido por **Adriano Cardoso**
