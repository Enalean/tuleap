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
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
    }

    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'MaillogPluginInfo')) {
            require_once('MaillogPluginInfo.class.php');
            $this->pluginInfo = new MaillogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function site_admin_option_hook() {
        $name = $GLOBALS['Language']->getText('plugin_maillog', 'descriptor_name');
        echo '<li><a href="'.$this->getPluginPath().'/">'.$name.'</a></li>';
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
        if($request->isPost() && $request->get("delete") == "Delete") {
            include_once('MaillogDao.class.php');
            $dao = new MaillogDao(CodendiDataAccess::instance());
            $dao->deleteAllMessages();
            $GLOBALS['Response']->redirect($this->getPluginPath().'/');
        }
    }

    function display() {
        $params['title'] = "Maillog";
        $GLOBALS['HTML']->header($params);
        $this->listMessages();
        $GLOBALS['HTML']->footer($params);
    }

    function listMessages() {
        include_once('MaillogDao.class.php');
        $dao = new MaillogDao(CodendiDataAccess::instance());
        $dar = $dao->getAllMessages();
        $nb = $dao->getNbMessages();

        echo "<h1>List of emails sent by ".$GLOBALS['sys_name']."</h1>\n";
        echo "<div style=\"text-align: center;\">Nb messages: ".$nb."</div>\n";
        echo "<form name=\"maillog\" method=\"post\" action=\"?\">\n";
        echo "<p>\n";
        echo "<input type=\"submit\" name=\"delete\" value=\"Delete\" />\n";
        echo "</p>\n";

        $hp = Codendi_HTMLPurifier::instance();
        while($dar->valid()) {
            $row = $dar->current();

            $dar2 = $dao->getAllHeaders($row['id_message']);
            echo "<div style=\"background-color: lightgrey;\">\n";
            while($dar2->valid()) {
                $row2 = $dar2->current();
                echo "<strong>".$hp->purify($row2['name']).":</strong> ".$hp->purify($row2['value'])."<br>\n";
                $dar2->next();
            }
            echo "</div>\n";
            $input = preg_replace("/=\r?\n/", '', $row['html_body']);
            $input = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $input);
            echo $input;
            echo '<div style="width: 80em; font-family: monospace; margin-bottom: 0.5em;">'."\n";
            echo $hp->purify($row['body'], CODENDI_PURIFIER_BASIC);
            echo "</div>\n";

            $dar->next();
        }
        echo "</form>";
    }

}

?>
