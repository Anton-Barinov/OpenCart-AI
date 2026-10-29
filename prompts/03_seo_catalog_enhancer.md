# Prompt Scenario: Batch SEO Catalog Enhancer

> **Goal:** Scan catalog for products missing rich descriptions, meta titles, or keywords, and generate conversion-focused SEO copy.

---

### Russian Prompt (Промпт на русском)

```markdown
Используй MCP-инструменты OpenCart:
1. Вызови `oc_list_products` с лимитом 25.
2. Для каждого товара изучи детали через `oc_get_product`.
3. Найди товары с пустым или слишком коротким описанием (меньше 50 символов) или без meta_description.
4. Для каждого найденного товара сгенерируй:
   - Привлекательный meta_title (до 60 знаков);
   - Кликабельный meta_description (140-160 знаков) с ключевыми выгодами;
   - 5 релевантных поисковых тегов/ключевых слов;
   - Структурированное продающее описание в HTML (абзац с пользой + маркированный список характеристик).
5. Покажи результат в виде аккуратного превью.
```

---

### English Prompt

```markdown
Using OpenCart MCP tools:
1. Scan catalog items via `oc_list_products(limit: 25)`.
2. Inspect product details via `oc_get_product` for items missing meta descriptions or with thin copy (< 50 words).
3. Generate SEO metadata:
   - High-CTR Meta Title (< 60 chars)
   - Compelling Meta Description (140-160 chars)
   - 5 high-intent search tags
   - Clean HTML product description with feature highlights
4. Output a formatted table ready for catalog synchronization.
```
