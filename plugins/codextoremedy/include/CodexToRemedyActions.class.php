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
        return $dao->insertInCodexDB($params['id'], $params['user_id'], $params['summary'], $params['create_date'], $params['description'], $params['type'], $params['severity']);
    }

    /**
     * Insert ticket in RIF DB
     * 
     * @param Array $params collection of data that will be inserted
     * 
     * @return Boolean
     */
    function insertTicketInRIFDB($params) {
        $pdo = new CodexToRemedyDBDriver();
        $pdo->getPdo();
        //need more parameters from form: people_cc
        return $pdo->createTicket($params['summary'], $params['description'], $params['type'], $params['severity'], $params['create_date']);
    }

    /**
     * Send mail to SD after ticket submission
     *
     * @return void
     */
    function sendMailToSd($params) {
        $request = HTTPRequest::instance();

        $um = UserManager::instance();
        $user = $um->getCurrentUser();

        switch ($requestType = $params['type']) {
            case CodexToRemedy::TYPE_SUPPORT :
                $requestType = 'SUPPORT REQUEST';
                break;
            case CodexToRemedy::TYPE_ENHANCEMENT :
                $requestType = 'ENHANCEMENT REQUEST';
                break;
            default:
                $requestType = '';
                break;
        }
        switch ($severity = $params['severity']) {
            case CodexToRemedy::SEVERITY_MINOR :
                $severity = 'MINOR';
                break;
            case CodexToRemedy::SEVERITY_SERIOUS :
                $severity = 'SERIOUS';
                break;
            case CodexToRemedy::SEVERITY_CRITICAL :
                $severity = 'CRITICAL';
                break;
            default:
                $severity = '';
                break;
        }
        $summary = $params['summary'];
        $messageToSd = $params['description'];

        $to = 'codex-team@lists.codex.cro.st.com';
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
                $GLOBALS['Response']->addFeedBack('error',$GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_failed'));
            }
        } catch (Zend_Mail_Transport_Exception $e) {
                $GLOBALS['Response']->addFeedBack('error',$GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_failed'));
        }
    }
    // }}}
}

?>