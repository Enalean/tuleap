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
require_once('RequestHelpViews.class.php');
require_once('RequestHelpActions.class.php');

/**
 * RequestHelp */

class RequestHelp extends PluginControler {

    const SEVERITY_MINOR    = 1;
    const SEVERITY_SERIOUS  = 2;
    const SEVERITY_CRITICAL = 3;

    const TYPE_SUPPORT      = 1;
    const TYPE_ENHANCEMENT  = 2;

    protected $plugin;

    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct($plugin) {
        parent::__construct();
        $this->plugin = $plugin;
    }

    /**
     * Get the plugin
     *
     * @return requesthelpPlugin
     */
    function getPlugin() {
        return $this->plugin;
    }

    /**
     * Compute the request
     *
     * @return void
     */
    function request() {
        $request = $this->getRequest();

        if ($request->exist('action') && $this->getUser()->isLoggedIn()) {
            $vAction = new Valid_WhiteList('action', array('submit_ticket'));
            $vAction->required();
            $action = $request->getValidated('action', $vAction, false);
            switch ($action) {
                case 'submit_ticket':
                    $this->addAction('addTicket');
                    $this->addview('remedyPostSubmission');
                    break;
                default:
                    $this->addview('displayForm');
                    break;
            }
        } else {
            $this->addview('displayForm');
        }
    }

}

?>