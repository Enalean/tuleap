<?php
/**
 * Copyright (c) Enalean, 2015 — 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class TrackerPluginConfigRouter {

    /** @var TrackerPluginConfigController */
    private $controller;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        TrackerPluginConfigController $controller
    ) {
        $this->csrf       = $csrf;
        $this->controller = $controller;
    }

    public function process(Codendi_Request $request, Response $response, PFUser $user) {
        $this->checkUserIsSiteadmin($user, $response);

        switch ($request->get('action')) {
            case 'update':
                $this->csrf->check();
                $this->controller->update($request, $response);
                break;
            default:
                $this->controller->index($this->csrf, $response);
        }

    }

    private function checkUserIsSiteadmin(PFUser $user, Response $response) {
        if (! $user->isSuperUser()) {
            $response->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'perm_denied'));
            $response->redirect('/');
        }
    }
}
