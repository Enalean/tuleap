<?php
/*
 * Copyright © STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once('common/plugin/Plugin.class.php');

class maillogPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->_addHook('mail_sendmail', 'sendmail', false);
        $this->addHook('site_admin_option_hook');
        $this->addHook(Event::IS_IN_SITEADMIN);
    }

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'MaillogPluginInfo')) {
            require_once('MaillogPluginInfo.class.php');
            $this->pluginInfo = new MaillogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /** @see Event::IS_IN_SITEADMIN */
    public function is_in_siteadmin($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath().'/') === 0) {
            $params['is_in_siteadmin'] = true;
        }
    }

    public function site_admin_option_hook(array $params)
    {
        $params['plugins'][] = array(
            'label' => $GLOBALS['Language']->getText('plugin_maillog', 'descriptor_name'),
            'href'  => $this->getPluginPath() . '/'
        );
    }

    //$params['mail'] Mail
    //$params['header'] String array
    function sendmail($params) {
        include_once('MaillogDao.class.php');
        $dao = new MaillogDao(CodendiDataAccess::instance());
        $dao->insertMail($params['mail']);
    }

    function process() {
        $this->actions();
        $this->display();
    }

    function actions() {
        $request = HTTPRequest::instance();
        if($request->isPost() && $request->exist("delete")) {
            include_once('MaillogDao.class.php');
            $dao = new MaillogDao(CodendiDataAccess::instance());
            $dao->deleteAllMessages();
            $GLOBALS['Response']->redirect($this->getPluginPath().'/');
        }
    }

    private function displayMessage($id)
    {
        include_once('MaillogDao.class.php');
        $dao = new MaillogDao();
        $message = $dao->searchMessageById($id)->getRow();
        if ($message) {
            if (! $message['html_body']) {
                header('Content-Type: text/plain');
                echo $message['body'];
            } else {
                $input = preg_replace("/=\r?\n/", '', $message['html_body']);
                $input = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $input);

                echo '<base href="https://gmail.example.com/">';
                echo $input;
            }
        } else {
            echo 'Not found';
        }
    }

    function display() {
        $request = HTTPRequest::instance();
        $id = $request->get('id');
        if ($id) {
            $this->displayMessage($id);
            exit;
        }

        include_once('MaillogDao.class.php');
        $dao = new MaillogDao();

        $dar = $dao->getAllMessages();
        $nb = $dao->getNbMessages();
        $messages = array();
        foreach ($dar as $row) {
            $message = array(
                'id' => $row['id_message'],
            );

            foreach ($dao->getAllHeaders($row['id_message']) as $header) {
                if ($header['name'] === 'subject') {
                    $message['subject'] = $header['value'];
                }
                if ($header['name'] === 'to') {
                    $message['to'] = $header['value'];
                }
                if ($header['name'] === 'date') {
                    $message['date'] = $header['value'];
                }
            }

            $messages[] = $message;
        }

        $renderer = new \Tuleap\Admin\AdminPageRenderer();
        $renderer->renderAPresenter(
            'Maillog',
            __DIR__ .'/../templates',
            'maillog',
            array(
                'nb' => $nb,
                'messages' => $messages
            )
        );
    }
}
