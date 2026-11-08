# EDPay PHP SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Status: Base Scaffold](https://img.shields.io/badge/Status-Scaffold%20/%20Planning-amber.svg)](PLAN.md)

SDK oficial da plataforma **EDPay** para **PHP**. Integração agnóstica multi-gateway (M-Pesa, e-Mola, Cartões Visa/Mastercard, Apple Pay, Google Pay e futuros canais) em Moçambique e internacionalmente.

> [!NOTE]
> Este repositório contém a fundação arquitetural e o plano técnico do SDK. A implementação completa dos clientes e fixtures segue as especificações documentadas em [https://docs-pay.edutecka.com](https://docs-pay.edutecka.com).

## 🚀 Plugins & Plataformas E-Commerce Suportadas (Em Breve / Coming Soon)

Estamos também a desenvolver módulos oficiais para as seguintes plataformas de comércio eletrónico:
- 🛒 **WooCommerce (WordPress)** — *Em Breve*
- 🛍️ **Shopify** — *Em Breve*
- 🏬 **Magento (Adobe Commerce)** — *Em Breve*
- 📦 **PrestaShop** — *Em Breve*
- 💳 **OpenCart** — *Em Breve*

## 📦 Instalação

Consulte a documentação em [https://docs-pay.edutecka.com/sdks/](https://docs-pay.edutecka.com/sdks/) para instruções detalhadas de instalação e configuração.

## 🔑 Autenticação

A API EDPay utiliza autenticação por cabeçalhos dedicados:
- `X-EDPay-Public-Key: pk_live_... / pk_test_...`
- `X-EDPay-Private-Key: sk_live_... / sk_test_...`

## 📄 Licença

Distribuído sob a licença MIT. Consulte o arquivo [LICENSE](LICENSE) para mais informações.
