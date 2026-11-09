# Plano de Desenvolvimento — EDPay PHP SDK

## 1. Visão Geral e Princípios
- **Agnóstico de Gateway**: suporte a múltiplos gateways (M-Pesa, e-Mola, Cartões de Débito/Crédito, Apple Pay, Google Pay).
- **Tratamento de Idempotência**: envio automático do cabeçalho `Idempotency-Key` para mutações seguras.
- **Assinatura e Validação de Webhooks**: verificação criptográfica do cabeçalho `X-EDPay-Signature` via HMAC-SHA256.
- **Tratamento de Erros Estruturado**: mapeamento dos códigos de erro padrão da API (`400`, `401`, `409`, `422`, `500`).
- **Respeito ao Timeout USSD**: fluxo interativo de 60 segundos com suporte a polling assíncrono.

## 2. Roteiro de Implementação (Roadmap)
- [x] **Fase 0: Inicialização do Repositório & Arquitetura Base**
  - Criação da estrutura de diretórios padrão da linguagem
  - Configuração do gestor de dependências e empacotamento
  - Documentação inicial e guia de contribuição
- [ ] **Fase 1: Cliente HTTP e Autenticação**
  - Configuração de base URL, timeouts e headers (`X-EDPay-Public-Key`, `X-EDPay-Private-Key`)
  - Gestor de idempotência automático
- [ ] **Fase 2: Recursos Principais da API**
  - `Payments`: criar pagamento, consultar estado, cancelar
  - `Checkout`: criar sessões de checkout hospedado e obter dados
  - `PaymentLinks`: criar e desativar links de pagamento
  - `Invoices`: gestão de faturas com notas e descontos
  - `Customers`: gestão de clientes e identificação fiscal
  - `Wallets`: consulta de saldos e extratos
  - `PaymentMethods`: listar gateways e métodos públicos ativos
- [ ] **Fase 3: Módulo de Webhooks**
  - Parse de eventos JSON
  - Validação de assinatura com segredo da conta
- [ ] **Fase 4: Testes Unitários e Integração**
  - Suíte de testes com mocks da API
  - Testes contra ambiente Sandbox
- [ ] **Fase 5: Publicação no Gestor de Pacotes Oficial**

## 3. Plugins & Extensões (Coming Soon)
- Módulo WooCommerce (WordPress)
- Módulo Shopify App
- Módulo Magento 2
- Módulo PrestaShop 8
