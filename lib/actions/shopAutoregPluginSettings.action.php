<?php

class shopAutoregPluginSettingsAction extends waViewAction {

    protected $tpls = array(
        'EmaiMessage' => array(
            'name' => 'Текст Email-сообщения',
            'path' => 'plugins/autoreg/templates/EmaiMessage.html',
            'change_tpl' => false,
        ),
        'ContactInfo' => array(
            'name' => 'Шаблон выводимый на шаге контактной информации',
            'path' => 'plugins/autoreg/templates/ContactInfo.html',
            'change_tpl' => false,
        ),
        'ChangePasswordForm' => array(
            'name' => 'Шаблон формы изменения пароля в личном кабинете',
            'path' => 'plugins/autoreg/templates/ChangePasswordForm.html',
            'change_tpl' => false,
        ),
    );
    //protected $tpl_path = 'plugins/autoreg/templates/EmaiMessage.html';
    protected $plugin_id = array('shop', 'autoreg');

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get($this->plugin_id);

        /*
          $change_tpl = false;
          $template_path = wa()->getDataPath($this->tpl_path, false, 'shop', true);
          if (file_exists($template_path)) {
          $change_tpl = true;
          } else {
          $template_path = wa()->getAppPath($this->tpl_path, 'shop');
          }
          $template = file_get_contents($template_path);
         */

        foreach ($this->tpls as &$tpl) {
            $template_path = wa()->getDataPath($tpl['path'], false, 'shop', true);
            if (file_exists($template_path)) {
                $tpl['change_tpl'] = true;
            } else {
                $template_path = wa()->getAppPath($tpl['path'], 'shop');
            }
            $tpl['content'] = file_get_contents($template_path);
        }
        unset($tpl);


        $this->view->assign(
                array(
                    'settings' => $settings,
                    'tpls' => $this->tpls
                )
        );
    }

}
