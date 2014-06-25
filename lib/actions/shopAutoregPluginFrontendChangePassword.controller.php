<?php

class shopAutoregPluginFrontendChangePasswordController extends waJsonController {

    public function execute() {
        try {
            if (!wa()->getUser()->isAuth()) {
                throw new waException("Ошибка авторизации");
            }
            $password = waRequest::post('password');
            $new_password = waRequest::post('new_password');
            $password_confirm = waRequest::post('password_confirm');

            $contact = wa()->getUser();
            if ($contact['password'] == waContact::getPasswordHash($password)) {
                if (!$new_password) {
                    throw new waException("Введите новый пароль");
                }
                if ($new_password != $password_confirm) {
                    throw new waException("Введенные пароли не совпадают");
                }

                $contact->set('password', $new_password);
                $contact->save();

                $this->response['message'] = "Пароль успешно изменен";
            } else {
                throw new waException("Неверный пароль");
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
