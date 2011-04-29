<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 */

require_once('mvc/PluginAction.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/mail/Codendi_Mail.class.php');
require_once('CodexToRemedyDao.class.php');
require_once('CodexToRemedyDBDriver.class.php');

/**
 * CodexToRemedyActions
 */

class CodexToRemedyActions extends PluginAction {

    // {{{ Actions
    /**
     * Insert informations about the ticket in Codex database
     *
     * @param Array $params collection of data that will be inserted
     *
     * @return Boolean
     */
    function insertTicketInCodexDB($params) {
        $dao = new CodexToRemedyDao();
        return $dao->insertInCodexDB($params['id'], $params['user_id'], $params['summary'], $params['create_date'], $params['description'], $params['text_type'], $params['text_severity'], $params['cc']);
    }

    /**
     * Insert ticket in RIF DB
     * 
     * @param Array $params collection of data that will be inserted
     * 
     * @return Boolean
     */
    function insertTicketInRIFDB($params) {
        $oci = new CodexToRemedyDBDriver();
        $oci->getdbh();
        //need more parameters from form: people_cc
        return $oci->createTicket($params['summary'], $params['description'], $params['text_type'], $params['text_severity'], $params['create_date'], $params['cc']);
    }

    /**
     * Send mail to recipient after ticket submission
     *
     * @param Array $params collection of data used to build the mail
     * @param Int $recepient flag used to make out the recepient
     *
     * @return void
     */
    function sendMail($params, $recepient,&$requestStatus) {
        $request = HTTPRequest::instance();

        $um = UserManager::instance();
        $user = $um->getCurrentUser();

        $requestType = $params['text_type'];
        $severity = $params['text_severity'];
        $summary = $params['summary'];
        $messageToSd = $params['description'];

        if($recepient = '1') {
        $pluginManager = PluginManager::instance();
        $p = $pluginManager->getPluginByName('codextoremedy');
        $to = $p->getProperty('send_notif_mail_sd');
        }

        $from = $user->getEmail();
        // Send a notification message to the SD and CodexCC
        $mail = new Codendi_Mail();
        $mail->setTo($to);
        $mail->setFrom($from);

        $mail->setSubject($GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_subject', array($requestType, $user->getRealName())));

        $body = $GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_content', array($user->getRealName(), $user->getName(), $requestType, $severity, $summary, $messageToSd, $user->getEmail()));

        $mail->setBodyHtml($body);
        try {
            if(!$mail->send()) {
                $requestStatus = False;
            } else {
                $requestStatus = True;
            }
        } catch (Zend_Mail_Transport_Exception $e) {
                $GLOBALS['Response']->addFeedBack('error',$GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_failed'));
                $requestStatus = False;
        }
    }
    // }}}
}

?>