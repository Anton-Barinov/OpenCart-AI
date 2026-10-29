# Prompt Scenario: Customer Support Order Status & Tracking Assistant

> **Goal:** Assist customer support reps in looking up order status, tracking, and answering customer queries politely with privacy protection (PII masked).

---

### Russian Prompt (Промпт на русском)

```markdown
Используй подключенные MCP-инструменты OpenCart:
1. Клиент спрашивает: "Здравствуйте, где мой заказ #512?".
2. Вызови `oc_get_order` для order_id 512.
3. Проверь статус заказа, способ доставки и историю комментариев.
4. Сформируй вежливый, заботливый ответ клиенту в дружелюбном тоне:
   - Подтверди номер заказа и дату оформления;
   - Объясни текущий статус простыми словами;
   - Укажи трек-номер или ожидаемый срок доставки (если есть в истории заказа);
   - Предложи ответить на любые дополнительные вопросы.
```

---

### English Prompt

```markdown
Using OpenCart MCP tools:
1. Inbound customer inquiry: "Hi! What is the current status of order #512?".
2. Retrieve order data via `oc_get_order(order_id: 512)`.
3. Check status, shipping method, and historical status updates.
4. Compose an empathetic, professional customer service response:
   - Friendly greeting & order confirmation
   - Current processing/shipping stage
   - Estimated delivery / tracking code if available
   - Reassurance and contact point for further questions.
```
