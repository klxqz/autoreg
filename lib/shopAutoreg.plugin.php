<?php

class shopAutoregPlugin extends shopPlugin {

    public function frontendCheckout($params) {
        if (!$this->getSettings('status')) {
            return false;
        }

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
            $html = $view->fetch($template_path);
            return $html;
        }


        if (wa()->getUser()->isAuth()) {
            return false;
        }
        $checkout_data = wa()->getStorage()->get('shop/checkout');
        $contact = isset($checkout_data['contact']) && ($checkout_data['contact'] instanceof waContact) ? $checkout_data['contact'] : new waContact();

        $login = $contact->get('email', 'default');
        if (!$login) {
            return false;
        }

        $email_validator = new waEmailValidator();
        if (!$email_validator->isValid($login)) {
            return false;
        }

        $contact_model = new waContactModel();
        if ($contact_model->getByEmail($login, true)) {
            return false;
        }

        $contact->set('create_method', 'autoref-plugin');
        $contact->set('create_ip', waRequest::getIp());

        $contact->set('email', $login);
        $password = $this->randomPassword();
        $contact->set('password', $password);
        $contact->save();
        wa()->getAuth()->auth($contact);
        //$this->setSessionData('contact', $contact);

        //Добавляем подписку
        if (($this->getSettings('mailer_subscribe') == 'manually' && waRequest::post('mailer_subscribe', 0)) || $this->getSettings('mailer_subscribe') == 'auto') {
            try {
                $this->addSubscribe();
            } catch (Exception $ex) {
                
            }
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

    public function addSubscribe($name, $email) {

        if (!$locale || !waLocale::getInfo($locale)) {
            $locale = wa()->getLocale();
        }


        // Validate email
        $email = trim($email);
        if (!$email) {
            throw new waException('No email to subscribe.', 404);
        }
        $ev = new waEmailValidator();
        if (!$ev->isValid($email)) {
            throw new waException('Email is invalid.', 404);
        }

        // Get contact_id by email
        $cem = new waContactEmailsModel();
        $contact_id = $cem->getContactIdByNameEmail($name, $email);
        if (!$contact_id) {
            $contact_id = $cem->getContactIdByEmail($email);
        }

        // Create new contact if no id found
        if (!$contact_id) {
            $contact = new waContact();
            $contact['locale'] = $locale;
            $contact['email'] = $email;
            if ($name) {
                $contact['name'] = $name;
            }
            $contact['create_method'] = 'subscriber';
            if ($contact->save()) {
                throw new waException('Unable to create contact.', 500);
            }
            $contact_id = $contact->getId();
        }

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

    /*
      protected function setSessionData($key, $value) {
      $data = wa()->getStorage()->get('shop/cart', array());
      $data[$key] = $value;
      wa()->getStorage()->set('shop/cart', $data);
      }
     */
}
