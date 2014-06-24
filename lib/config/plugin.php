<?php

return array(
    'name' => 'Принудительная регистрация',
    'description' => 'Автоматическая регистрация покупателей',
    'vendor' => '985310',
    'version' => '1.0.0',
    'img' => 'img/autoreg.png',
    'frontend' => true,
    'shop_settings' => true,
    'handlers' => array(
        'frontend_checkout' => 'frontendCheckout'
    ),
);
