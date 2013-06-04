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
require_once('RequestHelpDao.class.php');
require_once('RequestHelpDBDriver.class.php');

/**
 * RequestHelpActions
 */
class RequestHelpActions extends PluginAction {

    const MAX_SUMMARY_LENGTH     = 128;
    const MAX_DESCRIPTION_LENGTH = 4000;
    // {{{ Actions
    /**
    * Validate request values
    *
    * @param HTTPRequest $request request containing form values
    *
    * @return Array
    */
    function validateRequest($request) {
        $purifier = Codendi_HTMLPurifier::instance();
        $status   = true;
        $invalid  = array();
        $valid    = new Valid_String('request_summary');
        $valid->required();
        $summary = trim($request->get('request_summary'));
        if ($request->valid($valid) && strlen($summary) < self::MAX_SUMMARY_LENGTH && $summary != '') {
            $params['summary'] = $purifier->purify($summary);
        } else {
            $status = false;
            $invalid[] = $GLOBALS['Language']->getText('plugin_requesthelp', 'summary');
        }
        $valid = new Valid_Text('request_description');
        $valid->required();
        $description = trim($request->get('request_description'));
        $defaultDescription = $GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_default_description');
        if ($request->valid($valid) && strlen($description) < self::MAX_DESCRIPTION_LENGTH && $description != '' && $description != $defaultDescription) {
            $params['description'] = $purifier->purify($description);
        } else {
            $status    = false;
            $invalid[] = 'Description';
        }
        $valid = new Valid_UInt('type');
        $valid->required();
        if ($request->valid($valid)) {
            $requestType = $request->get('type');
            $params['type'] = $requestType;
            switch ($requestType) {
                case RequestHelp::TYPE_SUPPORT :
                    $params['text_type'] = $this->_getPluginProperty('support_request');
                    break;
                case RequestHelp::TYPE_ENHANCEMENT :
                    $params['text_type'] = $this->_getPluginProperty('enhancement_request');
                    break;
                default:
                    $status = false;
                    $invalid[] = 'Type';
                    break;
            }
        } else {
            $status = false;
            $invalid[] = 'Type';
        }
        $valid = new Valid_UInt('severity');
        $valid->required();
        if ($request->valid($valid)) {
            $severity = $request->get('severity');
            $params['severity'] = $severity;
            switch ($severity) {
                case RequestHelp::SEVERITY_MINOR :
                    $params['text_severity'] = 'Minor';
                    break;
                case RequestHelp::SEVERITY_SERIOUS :
                    $params['text_severity'] = 'Serious';
                    break;
                case RequestHelp::SEVERITY_CRITICAL :
                    $params['text_severity'] = 'Critical';
                    break;
                default:
                    $status = false;
                    $invalid[] = $GLOBALS['Language']->getText('plugin_requesthelp', 'severity');
                    break;
            }
        } else {
            $status = false;
            $invalid[] = $GLOBALS['Language']->getText('plugin_requesthelp', 'severity');
        }
        $cc         = array();
        $mails      = array_map('trim', preg_split('/[,;]/', $request->get('cc')));
        $rule       = new Rule_Email();
        $um         = $this->_getUserManager();
        $invalidCc  = array();
        foreach ($mails as $mail) {
            if ($rule->isValid($mail)) {
                $cc[] = $mail;
            } else {
                if (trim($mail) != '') {
                    $user = $um->findUser($mail);
                    if ($user) {
                        $mail = $user->getUserName();
                        if ($mail) {
                            $cc[] = $mail;
                        } else {
                            $invalidCc[] = $mail;
                        }
                    } else {
                        $invalidCc[] = $mail;
                    }
                }
            }
        }
        if (!empty($invalidCc)) {
            $c = $this->getController();
            $c->addWarn($GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_invalid_cc', implode(", ", $invalidCc)));
        }
        $params['cc'] = implode(";", $cc);
        return array('status' => $status, 'params' => $params, 'invalid' => $invalid);
    }

    /**
     * Insert informations about the ticket in Codex database
     *
     * @param Array $params collection of data that will be inserted
     *
     * @return Boolean
     */
    function insertTicketInCodexDB($params) {
        $dao = new RequestHelpDao();
        return $dao->insertInCodexDB($params['user_id'], $params['summary'], $params['create_date'], $params['description'], $params['type'], $params['severity'], $params['cc'], $params['ticket_id']);
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
            $requester = strtoupper($this->_getRequesterLdapLogin());
            $oci = new RequestHelpDBDriver($this->getController()->getPlugin());
            $oci->getdbh();
            $ccMails = array_map('trim', preg_split('/[;]/', $params['cc']));
            $cc = '';
            $um   = $this->_getUserManager();
            $rule = new Rule_Email();
            foreach ($ccMails as $ccMail) {
                if ($ccMail != '') {
                    if ($rule->isValid($ccMail)) {
                        $cc .= $ccMail.';';
                    } else {
                        $email = $um->getUserByUserName($ccMail)->getEmail();
                        $cc .= $email.';';
                    }
                }
            }
            return $oci->createTicket($params['summary'], $params['description'], $params['text_type'], $params['text_severity'], $cc, $requester);
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Send mail to recipient after ticket submission
     *
     * @param Array $params    collection of data used to build the mail
     *
     * @return void
     */
    function sendMail($params) {
        $um   = $this->_getUserManager();
        $user = $um->getCurrentUser();

        $ticketId    = $params['ticket_id'];
        $requestType = $params['text_type'];
        $severity    = $params['text_severity'];
        $summary     = $params['summary'];
        $messageToSd = $params['description'];
        $cc          = $params['cc'];

        $pluginManager = $this->_getPluginManager();
        $p = $pluginManager->getPluginByName('requesthelp');
        if (!$from = $p->getProperty('send_notif_mail_from')) {
            $from = 'noreply@codex.cro.st.com';
        }
        // Send a notification message to the SD and CodexCC
        $mail = $this->_getCodendiMail();
        $mail->setFrom($from);

        $separator     = '<tr><td><HR color="midnightblue" size="3"></td></tr>';
        $noreply_alert = '<tr><td><span style="font-size:10.0pt;font-family:Verdana,font:sans-serif;color:red">'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_mail_noreply').'.</span></td><tr></table>';
        $section_span  = '<span style="font-size:10.0pt;font-family:Verdana,font:sans-serif;color:#009900">';
        $title_span    = '<span style="font-size:10.0pt;font-family:Verdana;font:sans-serif;color:navy" >';
        $content_span  = '<span style="font-size:10.0pt;font-family:Verdana,font:sans-serif" >';
        $core_mail     = $separator.$section_span.'<b>Ticket Details</b></span>'.
                         '<table>'.
                         '<tr><td>'.$title_span.'<b>Ticket : </b></span></td><td>'.$content_span.$ticketId.'</span></td></tr>'.
                         '<tr><td>'.$title_span.'<b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'type').' : </b></span></td><td>'.$content_span.$requestType.'</span></td></tr>'.
                         '<tr><td>'.$title_span.'<b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'severity').' : </b></span></td><td>'.$content_span.$severity.'</span></td></tr>'.
                         '<tr><td>'.$title_span.'<b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'summary').' : </b></span></td><td>'.$content_span.'<pre>'.$summary.'</pre></span></td></tr>'.
                         '<tr><td>'.$title_span.'<b>Description : </b></span></td></tr><tr></table><p>'.$content_span.'<pre>'.$messageToSd .'</pre></span></p>'.
        $separator.
        $section_span.'<b>Requester and notification details</b></span>'.
                      '<table>'.
                      '<tr><p><td>'.$title_span.'<b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_mail_submitter').' : </b></span></td><td>'.$content_span.$user->getRealName().' (<em>'.$user->getName().'</em>) <a href="mailto:'.$user->getEmail().'">'.$user->getEmail().'</a></td></span></tr>';
        if ($cc != '') {
            $core_mail .= '<tr><td>'.$title_span.'<b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_mail_cc').' : </b></span></td><td>'.$content_span;
            $ccMails = array_map('trim', preg_split('/[;]/', $cc));
            $rule = new Rule_Email();
            foreach ($ccMails as $ccMail) {
                if ($rule->isValid($ccMail)) {
                    $core_mail .= ' <a href="mailto:'.$ccMail.'">'.$ccMail.'</a>';
                } else {
                    $email = $um->getUserByUserName($ccMail)->getEmail();
                    $core_mail .= ' <a href="mailto:'.$ccMail.'">'.$email.'</a>';
                }
            }
            $core_mail .= '</td></span></p></tr>';
        }
        $core_mail .= '</table>';

        if (!$to = $p->getProperty('send_notif_mail_sd')) {
            $to = 'codex-team@lists.codex.cro.st.com';
        }
        $mail->setSubject($GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_mail_subject', array($severity, $summary)));
        $body = '<table><tr><td>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_mail_support', $user->getRealName()).'.</td></tr>'.$noreply_alert.$core_mail;

        $mail->setTo($to);
        $mail->setBodyHtml($body, Codendi_Mail::DISCARD_COMMON_LOOK_AND_FEEL);
        try {
            if (!$mail->send()) {
                $requestStatus = false;
            } else {
                $requestStatus = true;
            }
        } catch (Zend_Mail_Transport_Exception $e) {
            $GLOBALS['Response']->addFeedBack('error', $GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_mail_failed'));
            $requestStatus = false;
        }
        return $requestStatus;
    }

    /**
     * Main action of the plugin, gathering all actions
     *
     * @return void
     */
    function addTicket() {
        $c                     = $this->getController();
        $um                    = $this->_getUserManager();
        $user                  = $um->getCurrentUser();
        $request               = $c->getRequest();
        $validation            = $this->validateRequest($request);
        $status                = $validation['status'];
        $params                = $validation['params'];
        if ($status) {
            $params['user_id']     = $user->getId();
            $params['create_date'] = time();
            $ticketId = $this->insertTicketInRIFDB($params);
            $params['ticket_id'] = $ticketId;
            if ($ticketId && $this->insertTicketInCodexDB($params, $ticketId)) {
                $this->sendMail($params);
                $c->addData(array('status' => true));
                $c->addInfo($GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_ticket_submission_success'), CODENDI_PURIFIER_LIGHT);
            } else {
                $c->addData(array('status' => false, 'params' => $params));
                $c->addError($GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_ticket_submission_fail'));
            }
        } else {
            $c->addData(array('status' => false, 'params' => $params));
            $c->addError($GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_invalid_params', implode(", ", $validation['invalid'])));
        }
    }
    // }}}

    /**
     * Wrapper for tests
     *
     * @return UserManager
     */
    function _getUserManager() {
        return UserManager::instance();
    }

    /**
     * Return LDAP login stored in DB corresponding to given userId.
     *
     * @return String requester login
     */
    function _getRequesterLdapLogin() {
        $um   = $this->_getUserManager();
        $user = $um->getCurrentUser();
        $pluginManager = $this->_getPluginManager();
        $ldapPlugin    = $pluginManager->getPluginByName('ldap');
        if ($ldapPlugin && $pluginManager->isPluginAvailable($ldapPlugin)) {
            $ldapUm = new LDAP_UserManager($ldapPlugin->getLdap());
            $userId[] = $user->getId();
            $ldapLogin = $ldapUm->getLdapLoginFromUserIds($userId);
            if ($ldapLogin && !$ldapLogin->isError()&& $ldapLogin->rowCount()> 0) {
                $ldapLoginArray = $ldapLogin->getRow();
                $requester = $ldapLoginArray['ldap_uid'];
            } else {
                $requester = $this->_getPluginProperty('requesthelp_submitter');
            }
        } else {
            $requester = $this->_getPluginProperty('requesthelp_submitter');
        }
        return $requester;
    }

    /**
     * Retrieve request help plugin settings value
     *
     * @return String
     */
    function _getPluginProperty($property) {
        return $this->getController()->getPlugin()->getProperty($property);
    }

    /**
     * Wrapper for tests
     *
     * @return Codendi_Mail
     */
    function _getCodendiMail() {
        return new Codendi_Mail();
    }

    /**
     * Wrapper for tests
     *
     * @return PluginManager
     */
    function _getPluginManager() {
        return PluginManager::instance();

    }
}

?>
