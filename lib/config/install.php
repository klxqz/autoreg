<?php
$plugin_id = array('shop', 'autoreg');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
$app_settings_model->set($plugin_id, 'length', '8');
$app_settings_model->set($plugin_id, 'subject', 'Регистрация в магазине успешно пройдена');
$app_settings_model->set($plugin_id, 'mailer_subscribe', 'auto');

