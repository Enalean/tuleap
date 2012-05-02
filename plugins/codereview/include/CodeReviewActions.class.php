<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/mvc/Actions.class.php');

/**
 * codereview
 */
class CodeReviewActions extends Actions {

    /**
     *
     * @var PluginController
     */
    protected $controller;

    /**
     *
     * @var HTTPRequest
     */
    protected $request;

    /**
     * Class constructor
     *
     * @param CodeReview $controller Plugin controller
     *
     * @return Void
     */
    function __construct($controller, $view=null) {
        parent::Actions($controller);
        $this->controller = $controller;
        $this->request    = $controller->getRequest();
    }

    /**
    * Validate request values
    *
    * @return Array
    */
    function validateRequest() {
        $status  = true;
        $invalid = array();

        $valid  = new Valid_String('codereview_server_url');
        $server = trim($this->request->get('codereview_server_url'));
        if ($this->request->valid($valid) && $server != '') {
            $params['server'] = $server;
        } else {
            $status    = false;
            $invalid[] = 'server';
        }

        $valid      = new Valid_String('codereview_repository_url');
        $repository = trim($this->request->get('codereview_repository_url'));
        if ($this->request->valid($valid) && $repository != '') {
            $params['repository'] = $repository;
        } else {
            $status    = false;
            $invalid[] = 'repository';
        }

        $valid   = new Valid_String('codereview_summary');
        $summary = trim($this->request->get('codereview_summary'));
        if ($this->request->valid($valid) && $summary != '') {
            $params['summary'] = $summary;
        } else {
            $status    = false;
            $invalid[] = 'Description';
        }

        $valid     = new Valid_String('codereview_revision_range');
        $revisions = trim($this->request->get('codereview_revision_range'));
        if ($this->request->valid($valid) && $revisions != '') {
            $params['revisions'] = $revisions;
        } else {
            $status    = false;
            $invalid[] = 'revisions';
        }

        $valid       = new Valid_String('codereview_description');
        $description = trim($this->request->get('codereview_description'));
        if ($this->request->valid($valid) && $description != '') {
            $params['description'] = $description;
        } else {
            $status    = false;
            $invalid[] = 'description';
        }
        return array('status' => $status, 'params' => $params, 'invalid' => $invalid);
}

}
?>