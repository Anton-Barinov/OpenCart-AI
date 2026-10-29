<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-opencart-ai" data-toggle="tooltip" title="<?php echo isset($button_save) ? $button_save : 'Сохранить'; ?>" class="btn btn-primary"><i class="fa fa-save"></i> Сохранить</button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo isset($button_cancel) ? $button_cancel : 'Отмена'; ?>" class="btn btn-default"><i class="fa fa-reply"></i> Отмена</a>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-cogs"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-opencart-ai" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><i class="fa fa-sliders"></i> Основные настройки</a></li>
            <li><a href="#tab-integration" data-toggle="tab"><i class="fa fa-plug"></i> Подключение к Claude / Cursor</a></li>
            <li><a href="#tab-prompts" data-toggle="tab"><i class="fa fa-lightbulb-o"></i> Готовые сценарии и промпты</a></li>
          </ul>

          <div class="tab-content">
            <!-- TAB 1: GENERAL -->
            <div class="tab-pane active" id="tab-general">
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-status">Статус модуля</label>
                <div class="col-sm-10">
                  <select name="module_opencart_ai_status" id="input-status" class="form-control">
                    <?php if ($module_opencart_ai_status) { ?>
                    <option value="1" selected="selected">Включено</option>
                    <option value="0">Отключено</option>
                    <?php } else { ?>
                    <option value="1">Включено</option>
                    <option value="0" selected="selected">Отключено</option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-token"><span data-toggle="tooltip" title="Секретный токен авторизации для внешних ИИ-клиентов (Bearer Token)">Секретный API токен</span></label>
                <div class="col-sm-8">
                  <div class="input-group">
                    <input type="text" name="module_opencart_ai_token" value="<?php echo $module_opencart_ai_token; ?>" id="input-token" class="form-control" readonly />
                    <span class="input-group-btn">
                      <button type="button" class="btn btn-default" onclick="copyToken()" title="Скопировать"><i class="fa fa-copy"></i> Копировать</button>
                      <button type="button" class="btn btn-warning" onclick="generateNewToken()" title="Сгенерировать новый"><i class="fa fa-refresh"></i> Новый токен</button>
                    </span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-endpoint">URL эндпоинта MCP</label>
                <div class="col-sm-10">
                  <input type="text" value="<?php echo $endpoint_url; ?>" id="input-endpoint" class="form-control" readonly />
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-mutations"><span data-toggle="tooltip" title="По умолчанию выключено. Если включить, ИИ сможет менять цены, остатки и статусы заказов.">Режим изменения данных (Mutation Mode)</span></label>
                <div class="col-sm-10">
                  <select name="module_opencart_ai_allow_mutations" id="input-mutations" class="form-control">
                    <?php if ($module_opencart_ai_allow_mutations) { ?>
                    <option value="1" selected="selected">Включено (ИИ может изменять цены и остатки)</option>
                    <option value="0">Выключено (Только чтение / Безопасный режим)</option>
                    <?php } else { ?>
                    <option value="1">Включено (ИИ может изменять цены и остатки)</option>
                    <option value="0" selected="selected">Выключено (Только чтение / Безопасный режим)</option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-pii"><span data-toggle="tooltip" title="Автоматически маскирует пароли, email и номера телефонов клиентов звездочками (***).">Маскирование GDPR / PII данных</span></label>
                <div class="col-sm-10">
                  <select name="module_opencart_ai_mask_pii" id="input-pii" class="form-control">
                    <?php if ($module_opencart_ai_mask_pii) { ?>
                    <option value="1" selected="selected">Да (Рекомендуется)</option>
                    <option value="0">Нет</option>
                    <?php } else { ?>
                    <option value="1">Да (Рекомендуется)</option>
                    <option value="0" selected="selected">Нет</option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-ips"><span data-toggle="tooltip" title="Оставьте пустым, чтобы разрешить доступ с любых IP-адресов.">Белый список IP-адресов</span></label>
                <div class="col-sm-10">
                  <textarea name="module_opencart_ai_allowed_ips" rows="3" id="input-ips" class="form-control" placeholder="127.0.0.1, 192.168.1.100"><?php echo $module_opencart_ai_allowed_ips; ?></textarea>
                </div>
              </div>
            </div>

            <!-- TAB 2: INTEGRATION -->
            <div class="tab-pane" id="tab-integration">
              <div class="alert alert-info">
                <h4><i class="fa fa-info-circle"></i> Быстрое подключение к Claude Desktop</h4>
                <p>Добавьте следующий блок в файл конфигурации <code>claude_desktop_config.json</code>:</p>
                <ul>
                  <li><strong>macOS:</strong> <code>~/Library/Application Support/Claude/claude_desktop_config.json</code></li>
                  <li><strong>Windows:</strong> <code>%APPDATA%\Claude\claude_desktop_config.json</code></li>
                </ul>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-claude">Конфиг Claude Desktop</label>
                <div class="col-sm-10">
                  <textarea id="input-claude" rows="12" class="form-control" style="font-family: monospace; font-size: 12px;" readonly><?php echo $claude_snippet; ?></textarea>
                  <br />
                  <button type="button" class="btn btn-default" onclick="copyClaudeConfig()"><i class="fa fa-copy"></i> Скопировать конфигурацию Claude</button>
                </div>
              </div>
            </div>

            <!-- TAB 3: PROMPTS -->
            <div class="tab-pane" id="tab-prompts">
              <div class="row">
                <div class="col-md-6">
                  <div class="panel panel-info">
                    <div class="panel-heading"><h3 class="panel-title">📉 1. Снижение цен на неликвид (Markdown Sale)</h3></div>
                    <div class="panel-body">
                      <p><strong>Промпт для ИИ:</strong></p>
                      <blockquote>«Найди товары, которые не продавались за последние 90 дней со складом > 10 шт, и снизь их цену на 15%. Покажи мне таблицу изменений перед применением.»</blockquote>
                      <small class="text-muted">Инструменты: <code>oc_sales_summary</code>, <code>oc_list_products</code>, <code>oc_update_product_price</code></small>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="panel panel-warning">
                    <div class="panel-heading"><h3 class="panel-title">📦 2. Автозаказ заканчивающихся товаров</h3></div>
                    <div class="panel-body">
                      <p><strong>Промпт для ИИ:</strong></p>
                      <blockquote>«Проверь остатки в каталоге. Выведи список товаров с остатком &lt; 5 шт, которые заказывали за последние 30 дней. Рассчитай рекомендуемое количество для дозаказа.»</blockquote>
                      <small class="text-muted">Инструменты: <code>oc_low_stock_products</code>, <code>oc_sales_summary</code></small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript"><!--
function generateNewToken() {
    if (confirm('Сгенерировать новый секретный токен? Старый токен перестанет действовать после сохранения.')) {
        var tokenParam = '<?php echo $token_param; ?>';
        var actionUrl = 'index.php?route=extension/module/opencart_ai/generateToken&' + tokenParam;
        $.ajax({
            url: actionUrl,
            type: 'get',
            dataType: 'json',
            success: function(json) {
                if (json['status'] == 'success') {
                    $('#input-token').val(json['token']);
                    updateClaudeSnippet(json['token']);
                    alert('Новый токен сгенерирован. Нажмите "Сохранить", чтобы применить изменения.');
                } else {
                    alert('Ошибка: ' + (json['message'] || 'Не удалось сгенерировать токен'));
                }
            },
            error: function() {
                var chars = '0123456789abcdef';
                var res = '';
                for (var i = 0; i < 48; i++) {
                    res += chars[Math.floor(Math.random() * chars.length)];
                }
                $('#input-token').val(res);
                updateClaudeSnippet(res);
                alert('Токен сгенерирован локально. Нажмите "Сохранить", чтобы применить изменения.');
            }
        });
    }
}

function copyToken() {
    var copyText = document.getElementById("input-token");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Токен скопирован в буфер обмена!");
}

function copyClaudeConfig() {
    var copyText = document.getElementById("input-claude");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Конфигурация Claude Desktop скопирована в буфер обмена!");
}

function updateClaudeSnippet(newToken) {
    try {
        var cfg = JSON.parse($('#input-claude').val());
        if (cfg.mcpServers && cfg.mcpServers['opencart-store']) {
            cfg.mcpServers['opencart-store'].env.OPENCART_BEARER_TOKEN = newToken;
            $('#input-claude').val(JSON.stringify(cfg, null, 2));
        }
    } catch(e) {}
}
//--></script>
<?php echo $footer; ?>
