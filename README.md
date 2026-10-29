# 🤖 OpenCart-AI: Universal E-Commerce MCP Bridge

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP: 5.4 to 8.4+](https://img.shields.io/badge/PHP-5.4%20--%208.4%2B-777bb4.svg)](https://www.php.net)
[![OpenCart: 2.x | 3.x | 4.x](https://img.shields.io/badge/OpenCart-2.x%20%7C%203.x%20%7C%204.x-brightgreen.svg)](https://www.opencart.com)
[![Protocol: MCP](https://img.shields.io/badge/MCP-2024--11--05-orange.svg)](https://modelcontextprotocol.io)
[![Tests: 100% Passing](https://img.shields.io/badge/Tests-100%25%20Passing-success.svg)](tests/)

> Turn-key **Model Context Protocol (MCP)** bridge and autonomous AI agent for **OpenCart 2.x, 3.x, and 4.x** stores. Connect **Claude Desktop**, **Cursor**, and **ChatGPT** directly to your catalog, orders, and inventory with a 1-click OCMOD installer and zero-trust security controls.

---

## 💡 Why OpenCart-AI?

Over **100,000+ active online stores** run on OpenCart, yet merchants have been locked out of modern agentic AI workflows. Standard LLMs cannot read live store inventory, analyze sales trends, adjust prices, or process order workflows without fragile third-party scrapers or insecure database credentials.

**OpenCart-AI** bridges this gap:
- 🚀 **1-Click OCMOD Installation**: Upload `opencart-ai.ocmod.zip` via OpenCart Extension Installer — no manual database migrations or server reconfiguration required.
- 🔑 **Instant Token Generation**: Generate cryptographically secure 256-bit Bearer tokens in the admin panel with one click.
- 🛡️ **Zero-Trust Security & PII Protection**: Customer personal identifiable information (emails, telephone numbers, shipping addresses) is masked by default (`J*** D** <j***@example.com>`).
- 🛑 **Mutation Safety Valve**: Database write operations (price changes, inventory deductions, order status updates) are strictly disabled by default until explicitly authorized in store settings.
- 🌐 **IP Whitelisting**: Lock down AI bridge access to company VPNs, static corporate IPs, or local developer subnets.
- ⚡ **Full Backward & Forward Compatibility**: Runs smoothly on legacy PHP 5.4 installations (OpenCart 2.0–2.3) up to PHP 8.4 (OpenCart 3.x & 4.x).

---

## 🏗️ Architecture

```mermaid
flowchart TD
    subgraph AI_Clients["AI Assistants & IDEs"]
        Claude["Claude Desktop"]
        Cursor["Cursor IDE"]
        ChatGPT["ChatGPT / Custom Agents"]
    end

    subgraph Security_Shield["OpenCart-AI Gateway"]
        AuthCheck["Bearer Token Auth (256-bit)"]
        IPFilter["IP Whitelist Validator"]
        PIIMasker["Automatic PII Redactor"]
        MutationGuard["Mutation Permissions Check"]
    end

    subgraph OpenCart_Core["OpenCart Store Engine"]
        Endpoint["Catalog Controller (JSON-RPC 2.0)"]
        Schema["Dynamic Schema Adapter (2.x / 3.x / 4.x)"]
        Catalog["Catalog & Inventory"]
        Orders["Orders & Customers"]
        Analytics["Sales & Revenue Engine"]
    end

    Claude -->|MCP JSON-RPC / HTTP| AuthCheck
    Cursor -->|MCP JSON-RPC / HTTP| AuthCheck
    ChatGPT -->|MCP JSON-RPC / HTTP| AuthCheck

    AuthCheck --> IPFilter
    IPFilter --> MutationGuard
    MutationGuard --> Endpoint
    Endpoint --> Schema
    Schema --> Catalog
    Schema --> Orders
    Schema --> Analytics

    Catalog --> PIIMasker
    Orders --> PIIMasker
    Analytics --> PIIMasker
    PIIMasker --> AI_Clients
```

---

## ⚡ Real-World AI Scenarios & Prompts

Ready-to-use prompt workflows are provided in the [`prompts/`](prompts/) directory:

### 1. 📉 Clearance of Illiquid Goods (Снижение цен на неликвид)
> **Merchant asks:** *"Find products that haven't sold in the last 90 days with stock > 10, and lower their price by 15%."*
- **Workflow:** AI executes `oc_sales_summary(days=90)` and `oc_list_products()`, identifies dead stock, formats a preview table for merchant approval, and batches price updates via `oc_update_product_price()`.
- [Read full prompt & execution guide →](prompts/01_inventory_markdown.md)

### 2. 🔄 Order Refund & Restock Workflow (Возврат и пополнение остатка)
> **Merchant asks:** *"Process return and refund for order #402."*
- **Workflow:** AI fetches order lines and customer receipt via `oc_get_order(order_id=402)`, transitions order to Refunded via `oc_update_order_status()`, and restocks inventory using `oc_update_product_stock()`.
- [Read full prompt & execution guide →](prompts/02_order_refund_workflow.md)

### 3. ✨ Batch Catalog SEO Enhancer (Генерация SEO описаний)
> **Merchant asks:** *"Find all products missing meta descriptions and generate high-converting SEO copy."*
- **Workflow:** AI inspects catalog with `oc_list_products()`, detects empty meta tags, and generates CTR-optimized meta titles, descriptions, and 5 search tags.
- [Read full prompt & execution guide →](prompts/03_seo_catalog_enhancer.md)

### 4. 📊 Weekly Executive Briefing (Еженедельный отчет руководству)
> **Merchant asks:** *"Prepare this week's revenue and sales breakdown with inventory alerts."*
- **Workflow:** AI queries `oc_sales_summary(days=7)`, `oc_top_products()`, and `oc_low_stock_report(threshold=5)` to deliver an executive summary with actionable restocking advice.
- [Read full prompt & execution guide →](prompts/04_weekly_sales_analysis.md)

---

## 📦 Installation & Distributions

OpenCart-AI provides dedicated, battle-tested packages tailored for each OpenCart generation and distribution:

| Package | OpenCart Editions & Distributions | Template Engine | Admin Token | Installation Method |
| :--- | :--- | :---: | :---: | :--- |
| **`opencart-ai.ocmod.zip`** | **Universal Bundle** (all versions 2.0 – 4.x) | Twig + TPL | Auto-detect | Admin &rarr; Extensions &rarr; Installer |
| **`opencart-ai-oc3.ocmod.zip`** | **OpenCart 3.0.x / ocStore 3.0.x / BazarCMS 3.x / Prostore** | Twig | `user_token` | Admin &rarr; Extensions &rarr; Installer |
| **`opencart-ai-oc23.ocmod.zip`** | **OpenCart 2.3.x / ocStore 2.3.x / OpenCart.pro 2.3** | PHP TPL | `token` | Admin &rarr; Extensions &rarr; Installer |
| **`opencart-ai-oc2.ocmod.zip`** | **OpenCart 2.0.x – 2.2.x / ocStore 2.1** | PHP TPL | `token` | Admin &rarr; Extensions &rarr; Installer |
| **`opencart-ai-oc4.ocmod.zip`** | **OpenCart 4.0.x / 4.1.x** (Namespaced Architecture) | Twig | `user_token` | Admin &rarr; Extensions &rarr; Installer (`install.json`) |

### Option A: OpenCart Extension Installer (Recommended)
1. Download the package matching your store version from [Releases](https://github.com/Anton-Barinov/OpenCart-AI/releases) or build all packages locally:
   ```bash
   php bin/package-ocmod
   ```
2. Go to **Admin Panel** &rarr; **Extensions** &rarr; **Installer**.
3. Upload `opencart-ai-oc3.ocmod.zip` (or your store's version package).
4. Go to **Extensions** &rarr; **Modifications** and click the **Refresh** button (top right).
5. Go to **Extensions** &rarr; **Extensions** &rarr; select **Modules** &rarr; find **OpenCart-AI** and click **Install** &rarr; **Edit**.
6. Enable the bridge, click **Generate Key**, and save!

### Option B: Manual Upload
Copy the contents of the `upload/` folder into your OpenCart store root directory.

---

## 🔌 Connecting Clients

### Claude Desktop
Add the following to your `claude_desktop_config.json` (`~/Library/Application Support/Claude/claude_desktop_config.json` on macOS or `%APPDATA%\Claude\` on Windows):

```json
{
  "mcpServers": {
    "opencart-ai": {
      "url": "https://your-store.com/index.php?route=extension/module/opencart_ai/endpoint",
      "headers": {
        "Authorization": "Bearer YOUR_GENERATED_AI_MCP_TOKEN"
      }
    }
  }
}
```

### Cursor IDE
Add to your project's `.cursor/mcp.json` or Global Cursor Settings:

```json
{
  "mcpServers": {
    "opencart-ai": {
      "url": "https://your-store.com/index.php?route=extension/module/opencart_ai/endpoint",
      "headers": {
        "Authorization": "Bearer YOUR_GENERATED_AI_MCP_TOKEN"
      }
    }
  }
}
```

---

## 🛠️ Available MCP Tools

| Tool | Category | Mutations? | Description |
| :--- | :--- | :---: | :--- |
| `oc_list_products` | Catalog | No | List products with pagination, category filter, and search. |
| `oc_get_product` | Catalog | No | Comprehensive product details (attributes, prices, options, SEO URL). |
| `oc_search_products` | Catalog | No | Fast full-text search across name, model, SKU, and tags. |
| `oc_update_product_stock`| Catalog | **Yes** | Update inventory stock levels. |
| `oc_update_product_price`| Catalog | **Yes** | Update base retail price for a product. |
| `oc_list_categories` | Catalog | No | List categories with product counts and hierarchy. |
| `oc_get_category_tree` | Catalog | No | Retrieve entire nested category tree structure. |
| `oc_list_orders` | Orders | No | List store orders with status and date filtering (PII masked). |
| `oc_get_order` | Orders | No | Full order breakdown: items, options, totals, shipping. |
| `oc_update_order_status` | Orders | **Yes** | Update order status and optionally notify customer. |
| `oc_list_customers` | Customers| No | Customer accounts with order counts and spent totals (PII masked). |
| `oc_get_customer` | Customers| No | Customer profile and address history (PII masked). |
| `oc_sales_summary` | Analytics| No | Revenue, order counts, and AOV over arbitrary day windows. |
| `oc_low_stock_report` | Analytics| No | Products with stock at or below specified threshold. |
| `oc_top_products` | Analytics| No | Top bestselling products by units sold and revenue. |
| `oc_system_info` | System | No | Store version, PHP runtime, database stats, catalog metrics. |
| `oc_read_error_log` | System | No | Safely read the last N lines of OpenCart error.log. |
| `oc_list_modifications`| System | No | List installed OCMOD modifications and enabled states. |

---

## 🧪 Testing

The test suite runs with zero external dependencies across all supported PHP versions:

```bash
# Build OCMOD package
php bin/package-ocmod

# Run complete test suite (Endpoint, Admin Controller, Prompts, Package)
php tests/run_tests.php
```

Output:
```
🚀 Running OpenCart-AI Test Suite...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
▶ Endpoint Tests: ✅ PASSED
▶ Admin Controller Tests: ✅ PASSED
▶ Prompts & Configs Tests: ✅ PASSED
▶ OCMOD Package Tests: ✅ PASSED
================================================
🎉 ALL 4 TEST SUITES PASSED (100% SUCCESS)!
```

---

## 🤝 Ecosystem Cross-Links

- [OpenCart-MCP](https://github.com/Anton-Barinov/OpenCart-MCP) — Standalone CLI & Daemon MCP Server for OpenCart.
- [PHP-to-Binary](https://github.com/Anton-Barinov/PHP-to-Binary) — Compile standalone PHP CLI utilities into zero-dependency binaries.
- [Composer-Audit-ZeroTrust](https://github.com/Anton-Barinov/Composer-Audit-ZeroTrust) — Supply-chain security scanner for PHP vendor packages.
- [EOL-PHP-Armor](https://github.com/Anton-Barinov/EOL-PHP-Armor) — Automated security backport hub for legacy PHP.

---

## 📄 License

OpenCart-AI is open-source software licensed under the [MIT License](LICENSE).  
Authored by **Anton Barinov** ([@Anton-Barinov](https://github.com/Anton-Barinov)).
