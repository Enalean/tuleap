<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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
namespace Tuleap\Svn;

use Service;
use HTTPRequest;
use TemplateRendererFactory;

class ServiceSvn extends Service {

    public function renderInPage(HTTPRequest $request, $title, $template, $presenter = null) {
        $this->displayHeader($request, $title);

        if ($presenter) {
            $this->getRenderer()->renderToPage($template, $presenter);
        }

        $this->displayFooter();
        exit;
    }

    private function getRenderer() {
        return TemplateRendererFactory::build()->getRenderer(dirname(SVN_BASE_DIR).'/templates');
    }

    public function displayHeader(HTTPRequest $request, $title) {
        $toolbar = array();
        if ($this->userIsAdmin($request) && $request->get('repo_id')) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Admin'),
                'url'   => SVN_BASE_URL .'/?'. http_build_query(array(
                    'group_id'   => $request->get('group_id'),
                    'action'     => 'display-mail-notification',
                    'repo_id'    => $request->get('repo_id')
                ))
            );
        }

        $title       = $title.' - '.$GLOBALS['Language']->getText('plugin_svn', 'service_lbl_key');
        $breadcrumbs = array();
        parent::displayHeader($title, $breadcrumbs, $toolbar);
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    private function userIsAdmin(HTTPRequest $request) {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }
}
