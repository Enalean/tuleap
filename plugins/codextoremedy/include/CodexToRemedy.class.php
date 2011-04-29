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

require_once('mvc/PluginControler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('CodexToRemedyViews.class.php');
require_once('CodexToRemedyActions.class.php');

/**
 * CodexToRemedy */

class CodexToRemedy extends PluginControler {

    const SEVERITY_MINOR    = 1;
    const SEVERITY_SERIOUS  = 2;
    const SEVERITY_CRITICAL = 3;

    const TYPE_SUPPORT      = 1;
    const TYPE_ENHANCEMENT  = 2;

    const RECEPIENT_SD  = 1;
    const RECEPIENT_USER  = 2;

    /**
     * Compute the request
     *
     * @return void
     */
    function request() {
        $request = HTTPRequest::instance();
        $um      = UserManager::instance();
        $user    = $um->getCurrentUser();

        if ($request->exist('action') && $user->isLoggedIn()) {
            $vAction = new Valid_WhiteList('action', array('submit_ticket'));
            $vAction->required();
            $action = $request->getValidated('action', $vAction, false);
            switch ($action) {
                case 'submit_ticket':

                    // {{{ Example to test insertion in Codex DB
                    $params                = $this->validateRequest($request);
                    $params['id']          = rand(1, 100);
                    $params['user_id']     = $user->getId();
                    $params['create_date'] = time();
                    $this->addAction('sendMail', array($params, self::RECEPIENT_SD,&$requestStatus));
                    $this->addAction('insertTicketInCodexDB', array($params));
                    $this->addAction('insertTicketInRIFDB', array($params));
                    // }}}
                    $this->addview('remedyPostSubmission', array(&$requestStatus));
                    break;
                default:
                    break;
            }
        } else {
            $this->addView('remedyPostSubmission', array(&$requestStatus));
        }
    }

    function validateRequest($request) {
        $valid = new Valid_String('request_summary');
        $valid->required();
        if($this->request->valid($valid)) {
            $params['summary'] = $request->get('request_summary');
        }
        $valid = new Valid_Text('request_description');
        $valid->required();
        if($this->request->valid($valid)) {
            $params['description'] = $request->get('request_description');
        }
        $valid = new Valid_UInt('type');
        $valid->required();
        if($this->request->valid($valid)) {
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
        if($this->request->valid($valid)) {
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
        $mails      = array_map('trim', preg_split('/[,;]/', $this->request->get('cc')));
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
}

?>