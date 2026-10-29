# Prompt Scenario: Order Return & Refund Workflow

> **Goal:** Inspect customer order details, verify return eligibility, update order status to Refunded/Returned, and optionally replenish stock.

---

### Russian Prompt (Промпт на русском)

```markdown
Используй подключенные MCP-инструменты OpenCart:
1. Вызови `oc_get_order` для заказа #402.
2. Проверь статус заказа, состав товаров, общую сумму и дату создания.
3. Выведи краткую сводку: имя покупателя, сумму возврата и список позиций.
4. Спроси меня, нужно ли вернуть остатки товаров на склад.
5. После подтверждения:
   - Обнови статус заказа через `oc_update_order_status` на статус "Возврат / Refunded" (укажи ID соответствующего статуса) с комментарием: "Оформлен возврат средств через AI Assistant. Запрос клиента одобрен."
   - Если требуется вернуть товар на склад — вызови `oc_update_product_stock` для каждого возвращенного товара, увеличив остаток на соответствующее количество.
```

---

### English Prompt

```markdown
Using the connected OpenCart MCP tools:
1. Fetch order details for order #402 via `oc_get_order(order_id: 402)`.
2. Inspect the current order status, purchased line items, totals, and creation date.
3. Present a clear return authorization brief with customer name (PII masked), order total, and items.
4. Ask whether inventory should be replenished.
5. Upon confirmation:
   - Call `oc_update_order_status` to transition the order to Refunded/Returned with audit comment: "Refund processed via AI Assistant."
   - If stock replenishment requested, call `oc_update_product_stock` to increment inventory for each returned item.
```

---

### Key MCP Tools Used
- `oc_get_order`
- `oc_update_order_status`
- `oc_update_product_stock`
- `oc_get_product`
