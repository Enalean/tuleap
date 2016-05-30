<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\Deprecation;

use TemplateRendererFactory;
use Response;

class DeprecationController
{
    private $retriever;

    public function __construct(DeprecationRetriever $retriever)
    {
        $this->retriever = $retriever;
    }

    public function index(Response $response)
    {
        $title  = $GLOBALS['Language']->getText('plugin_tracker_deprecation_panel', 'title');
        $params = array(
            'title' => $title
        );
        $renderer  = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
        $presenter = new DeprecationPresenter($title, $this->getDeprecatedProjects());

        $response->header($params);
        $renderer->renderToPage('siteadmin-config/deprecation', $presenter);
        $response->footer($params);
    }

    private function getDeprecatedProjects()
    {
        $deprecated_projects = array();
        foreach ($this->retriever->getDeprecatedTrackersFields() as $deprecated_field) {
            $deprecated_projects[] = $deprecated_field;
        }

        return $deprecated_projects;
    }

    private function getAllProjects()
    {
        return $this->retriever->getDeprecatedTrackersFields();
    }
}
