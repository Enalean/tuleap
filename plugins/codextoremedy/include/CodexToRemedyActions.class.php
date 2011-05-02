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

    const RECEPIENT_SD   = 1;
    const RECEPIENT_USER = 2;

    // {{{ Actions
    /**
     * Validate request values
     *
     * @param HTTPRequest $request
     *
     * @return Array
     */
    function validateRequest($request) {
        $valid = new Valid_String('request_summary');
        $valid->required();
        if($request->valid($valid)) {
            $params['summary'] = $request->get('request_summary');
        }
        $valid = new Valid_Text('request_description');
        $valid->required();
        if($request->valid($valid)) {
            $params['description'] = $request->get('request_description');
        }
        $valid = new Valid_UInt('type');
        $valid->required();
        if($request->valid($valid)) {
            $requestType = $request->get('type');
            $params['type'] = $requestType;
            switch ($requestType) {
                case CodexToRemedy::TYPE_SUPPORT :
                    $params['text_type'] = 'SUPPORT REQUEST';
                    break;
                case CodexToRemedy::TYPE_ENHANCEMENT :
                    $params['text_type'] = 'ENHANCEMENT REQUEST';
                    break;
                default:
                    $params['text_type'] = '';
                    break;
            }
        }
        $valid = new Valid_UInt('severity');
        $valid->required();
        if($request->valid($valid)) {
            $severity = $request->get('severity');
            $params['severity'] = $severity;
            switch ($severity) {
                case CodexToRemedy::SEVERITY_MINOR :
                    $params['text_severity'] = 'Minor';
                    break;
                case CodexToRemedy::SEVERITY_SERIOUS :
                    $params['text_severity'] = 'Serious';
                    break;
                case CodexToRemedy::SEVERITY_CRITICAL :
                    $params['text_severity'] = 'Critical';
                    break;
                default:
                    $params['text_severity'] = '';
                    break;
            }
        }
        $cc = '';
        $mails      = array_map('trim', preg_split('/[,;]/', $request->get('cc')));
        $rule       = new Rule_Email();
        $um         = UserManager::instance();
        foreach ($mails as $mail) {
            if ($rule->isValid($mail)) {
                if ($cc == '') {
                    $cc = $mail;
                } else {
                    $cc .= ';'.$mail;
                }
            } else {
                $user = $um->findUser($mail);
                if ($user) {
                    $mail = $user->getEmail();
                    if ($mail) {
                        if ($cc == '') {
                            $cc = $mail;
                        } else {
                            $cc .= ';'.$mail;
                        }
                    }
                }
            }
        }
        $params['cc'] = $cc;
        return $params;
    }

    /**
     * Insert informations about the ticket in Codex database
     *
     * @param Array $params collection of data that will be inserted
     *
     * @return Boolean
     */
    function insertTicketInCodexDB($params) {
        $dao = new CodexToRemedyDao();
        return $dao->insertInCodexDB($params['id'], $params['user_id'], $params['summary'], $params['create_date'], $params['description'], $params['type'], $params['severity'], $params['cc']);
    }

    /**
     * Insert ticket in RIF DB
     * 
     * @param Array $params collection of data that will be inserted
     * 
     * @return Boolean
     */
    function insertTicketInRIFDB($params) {
        try {
            $oci = new CodexToRemedyDBDriver();
            $oci->getdbh();
            return $oci->createTicket($params['summary'], $params['description'], $params['text_type'], $params['text_severity'], $params['create_date']);
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Send mail to recipient after ticket submission
     *
     * @param Array $params collection of data used to build the mail
     * @param Int $recepient flag used to make out the recepient
     *
     * @return void
     */
    function sendMail($params, $recepient) {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();

        $requestType = $params['text_type'];
        $severity = $params['text_severity'];
        $summary = $params['summary'];
        $messageToSd = $params['description'];

        if($recepient == self::RECEPIENT_SD) {
            $pluginManager = PluginManager::instance();
            $p = $pluginManager->getPluginByName('codextoremedy');
            if (!$to = $p->getProperty('send_notif_mail_sd')) {
                $to = 'codex-team@lists.codex.cro.st.com';
            }
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
                $requestStatus = false;
            } else {
                $requestStatus = true;
            }
        } catch (Zend_Mail_Transport_Exception $e) {
                $GLOBALS['Response']->addFeedBack('error',$GLOBALS['Language']->getText('plugin_codextoremedy', 'codextoremedy_mail_failed'));
                $requestStatus = false;
        }
        return $requestStatus;
    }

    /**
     * Main action of the plugin, gathering all actions
     *
     * @return void
     */
    function AddTicket() {
        $c = $this->getController();
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        $request               = $this->getController()->getRequest();
        $params                = $this->validateRequest($request);
        $params['id']          = rand(1, 100);
        $params['user_id']     = $user->getId();
        $params['create_date'] = time();
        if($this->insertTicketInCodexDB($params)) {
            $this->sendMail($params, self::RECEPIENT_SD);
            // TODO : notify user
            if(!$this->insertTicketInRIFDB($params)) {
                // TODO : notify SD by the failure
            }
            $c->addData(array('status' => true));
        } else {
            $c->addData(array('status' => false));
        }
    }
    // }}}
}

?>