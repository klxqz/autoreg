<?php

class shopAutoregPlugin extends shopPlugin {

    public static function changePasswordForm() {
        $template_path = wa()->getDataPath('plugins/autoreg/templates/ChangePasswordForm.html', false, 'shop', true);
        if (!file_exists($template_path)) {
            $template_path = wa()->getAppPath('plugins/autoreg/templates/ChangePasswordForm.html', 'shop');
        }
        $view = wa()->getView();
        $html = $view->fetch($template_path);
        return $html;
    }

    public function frontendCheckout($params) {
        if (!$this->getSettings('status')) {
            return false;
        }
        $html = '';

        try {
            if ($params['step'] == 'contactinfo') {
                $template_path = wa()->getDataPath('plugins/autoreg/templates/ContactInfo.html', false, 'shop', true);
                if (!file_exists($template_path)) {
                    $template_path = wa()->getAppPath('plugins/autoreg/templates/ContactInfo.html', 'shop');
                }
                $view = wa()->getView();
                $view->assign(array(
                    'mailer_exists' => wa()->appExists('mailer'),
                    'mailer_subscribe' => $this->getSettings('mailer_subscribe')
                ));
                $html .= $view->fetch($template_path);
            }


            if (wa()->getUser()->isAuth()) {
                throw new waException("Пользователь уже авторизирован");
            }
            $checkout_data = wa()->getStorage()->get('shop/checkout');
            $contact = isset($checkout_data['contact']) && ($checkout_data['contact'] instanceof waContact) ? $checkout_data['contact'] : new waContact();

            $login = $contact->get('email', 'default');
            if (!$login) {
                throw new waException("Email не указан");
            }

            $email_validator = new waEmailValidator();
            if (!$email_validator->isValid($login)) {
                throw new waException("Неверный Email");
            }

            $contact_model = new waContactModel();
            if ($contact_model->getByEmail($login, true)) {
                throw new waException("Контакт с указанным Email уже существует");
            }

            $contact->set('create_method', 'autoreg-plugin');
            $contact->set('create_ip', waRequest::getIp());

            $contact->set('email', $login);
            $password = $this->randomPassword();
            $contact->set('password', $password);
            $contact->save();
            wa()->getAuth()->auth($contact);
            //Добавляем подписку
            if (wa()->appExists('mailer') && (($this->getSettings('mailer_subscribe') == 'manually' && waRequest::cookie('mailer_subscribe', 0)) || $this->getSettings('mailer_subscribe') == 'auto')) {
                $this->addSubscribe($contact->getId(), $login);
            }


            $general = wa('shop')->getConfig()->getGeneralSettings();
            $template_path = wa()->getDataPath('plugins/autoreg/templates/EmaiMessage.html', false, 'shop', true);
            if (!file_exists($template_path)) {
                $template_path = wa()->getAppPath('plugins/autoreg/templates/EmaiMessage.html', 'shop');
            }
            $view = wa()->getView();
            $view->assign(
                    array(
                        'contact' => $contact,
                        'login' => $login,
                        'password' => $password
                    )
            );
            $notification = $view->fetch($template_path);
            $message = new waMailMessage($this->getSettings('subject'), $notification);
            $message->setTo($login);
            $from = $general['email'];
            $message->setFrom($from, $general['name']);
            $message->send();
        } catch (Exception $e) {
            return $html;
        }
    }

    protected function randomPassword() {
        $pass = '';
        $chars = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $lenght = intval($this->getSettings('length')) ? $this->getSettings('length') : 8;
        for ($i = 0; $i < $lenght; $i++) {
            $n = rand(0, strlen($chars) - 1);
            $pass .= $chars{$n};
        }
        return $pass;
    }

    public function addSubscribe($contact_id, $email) {
        wa('mailer');
        // Remove contact from unsubscribers
        $um = new mailerUnsubscriberModel();
        $um->deleteByField(array(
            'email' => $email,
            'list_id' => array(0, 1),
        ));

        // Subscribe contact to default list (id=1)
        $sm = new mailerSubscriberModel();
        $sm->add($contact_id, 1, $email);
    }

}
