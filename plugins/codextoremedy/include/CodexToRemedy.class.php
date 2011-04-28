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
        $user = UserManager::instance()->getCurrentUser();

        if ($request->exist('action') && $user->isLoggedIn()) {
            switch ($request->get('action')) {
                case 'submit_ticket':

                    // {{{ Example to test insertion in Codex DB
                    $params['id']          = rand(1, 100);
                    $params['user_id']     = $user->getId();
                    $params['summary']     = $request->get('request_summary');
                    $params['create_date'] = time();
                    $params['description'] = $request->get('request_description');
                    $params['type']        = $request->get('type');
                    $params['severity']    = $request->get('severity');
                    $this->addAction('sendMail', array($params, self::RECEPIENT_SD));
                    $this->addAction('insertTicketInCodexDB', array($params));
                    $this->addAction('insertTicketInRIFDB', array($params));
                    // }}}

                    //$this->view = 'remedyForm';
                    break;
                default:
                    break;
            }
        } else {
            $this->addView('remedyForm');
        }
    }
}

?>