<?php

class shopAutoregPluginFrontendChangePasswordController extends waJsonController {

    public function execute() {
        try {


            $this->response['message'] = "Пароль успешно изменен";
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
