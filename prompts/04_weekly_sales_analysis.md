# Prompt Scenario: Weekly Executive Sales & Revenue Breakdown

> **Goal:** Produce an executive dashboard analysis of the past 7–30 days covering revenue, order volume, average order value, top performers, and inventory risk alerts.

---

### Russian Prompt (Промпт на русском)

```markdown
Используй подключенные MCP-инструменты OpenCart:
1. Вызови `oc_sales_summary` за 7 дней и за 30 дней.
2. Вызови `oc_top_products` (лимит 10) чтобы узнать лидеров продаж.
3. Вызови `oc_low_stock_report` (порог 5 штук) для выявления товаров на грани дефицита.
4. Вызови `oc_system_info` для получения общей сводки по магазину.
5. Подготовь лаконичный отчет для руководства:
   - Выручка и количество заказов за прошедшую неделю в сравнении с месячной динамикой;
   - Средний чек (AOV);
   - Топ-3 бестселлера недели;
   - Риски: товары из топа продаж с критически низким остатком (< 5 шт);
   - 3 рекомендации по росту конверсии и маркетинговым акциям на следующую неделю.
```

---

### English Prompt

```markdown
Using OpenCart MCP tools:
1. Query sales summary via `oc_sales_summary(days: 7)` and `oc_sales_summary(days: 30)`.
2. Retrieve top sellers with `oc_top_products(limit: 10)`.
3. Detect stock risks via `oc_low_stock_report(threshold: 5)`.
4. Compose an executive weekly performance briefing:
   - Revenue, orders, and Average Order Value (AOV)
   - Top-3 grossing products
   - Out-of-stock risk matrix (top items with stock < 5)
   - 3 strategic recommendations for the upcoming week.
```
