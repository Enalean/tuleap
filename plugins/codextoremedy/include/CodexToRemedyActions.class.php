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

require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/mail/Codendi_Mail.class.php');
require_once('CodexToRemedyDao.class.php');

/**
 * CodexToRemedyActions
 */

class CodexToRemedyActions extends Actions {

    /**
     * Constructor
     *
     * @param CodexToRemedy $controler the controller
     *
     * @return void
     */
    function CodexToRemedyActions($controler) {
        $this->Actions($controler);
    }

    // {{{ Actions
    /**
     * Insert informations about the ticket in Codex database
     *
     * @return Boolean
     */
    function insertInPluginDB($params) {
        $dao = new CodexToRemedyDao();
        return $dao->insertInCodexDB($params['id'], $params['user_id'], $params['summary'], $params['create_date'], $params['description'], $params['type'], $params['severity']);
    }

    /**
     * Send mail to SD after ticket submission
     *
     * @param User    $user        The requester
     * @param String  $requestType The type of the request
     * @param String  $severity    The severity
     * @param String  $summary     The request summary
     * @param String  $messageToSd The request description
     */
    function sendMailToSd() {
        $request = HTTPRequest::instance();

        $user_id = $request->get('user');
        $um = UserManager::instance();
        $user = $um->getUserById($user_id);

        $requestType = $request->get('type');
        $severity = $request->get('severity');
        $summary = $request->get('request_summary');
        $messageToSd = $request->get('request_description');

        $to = 'codex-team-bounces@lists.codex.cro.st.com';
        $from = $user->getEmail();
        // Send a notification message to the SD and CodexCC
        $mail = new Codendi_Mail();
        $mail->setTo($to);
        $mail->setFrom($from);

        $mail->setSubject($GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_subject', array($requestType, $user->getRealName())));

        $body = $GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_content', array($user->getRealName(), $user->getName(), $requestType, $severity, $summary, $messageToSd, $user->getEmail()));

        $mail->setBodyHtml($body);
        try {
            if(!$mail->send())
            { $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_failed').'</div>'; }
        } catch (Zend_Mail_Transport_Exception $e) {
            $GLOBALS['feedback'] .= '<div>'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_failed').'</div>';
        }

    }
    // }}}
}

?>