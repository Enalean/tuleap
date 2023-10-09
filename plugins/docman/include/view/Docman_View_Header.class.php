<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Layout\HeaderConfigurationBuilder;

/* abstract */ class Docman_View_Header extends Docman_View_View
{
    public function _header($params)
    {
        if (! headers_sent()) {
            header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        }

        if (isset($params['title'])) {
            $title = $params['title'];
        } else {
            $title = $this->_getTitle($params);
        }

        $service = null;
        $project = $this->getProjectFromParams($params);

        if ($project) {
            $service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
            \assert($service instanceof Tuleap\Docman\ServiceDocman);
        }

        if ($project) {
            if (! $service) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'Service unavailable in project');
                $GLOBALS['Response']->redirect('/');
                return;
            }

            if (isset($params['pv']) && $params['pv'] > 0) {
                $params = \Tuleap\Layout\HeaderConfigurationBuilder::get($title)
                    ->inProject($project, (string) $service->getId())
                    ->withBodyClass(['service-' . DocmanPlugin::SERVICE_SHORTNAME])
                    ->withPrinterVersion($params['pv'])
                    ->build();
                $GLOBALS['HTML']->pv_header($params);
                return;
            }

            $service->displayHeader($title, $this->getBreadcrumbs($params, $project, $service), $this->getToolbar($params));
        } else {
            $GLOBALS['HTML']->includeCalendarScripts();

            $params = HeaderConfigurationBuilder::get($title)
                ->withBodyClass(['docman-body'])
                ->build();

            site_header($params);
        }
    }

    protected function getBreadcrumbs(array $params, Project $project, \Tuleap\Docman\ServiceDocman $service): array
    {
        return [];
    }

    protected function getToolbar(array $params)
    {
        return [];
    }

    /* protected */ public function _getTitle($params)
    {
        $title   = '';
        $project = $this->getProjectFromParams($params);
        if ($project) {
            $title .= Codendi_HTMLPurifier::instance()->purify($project->getPublicName()) . ' - ';
        }
        $title .= dgettext('tuleap-docman', 'Project Documentation');

        return $title;
    }

    protected function getUnconvertedTitle(array $params)
    {
        $title   = '';
        $project = $this->getProjectFromParams($params);
        if ($project) {
            $title .= $project->getPublicName() . ' - ';
        }
        $title .= dgettext('tuleap-docman', 'Project Documentation');

        return $title;
    }

    /* protected */ public function _footer($params)
    {
        if (isset($params['pv']) && $params['pv'] > 0) {
            $GLOBALS['HTML']->pv_footer();
        } else {
            $GLOBALS['HTML']->footer([]);
        }
    }

    /* protected */ public function _feedback($params)
    {
        //$this->_controller->feedback->display();
    }

    private function getProjectIdFromParams($params)
    {
        $project_id = null;
        if (isset($params['group_id'])) {
            $project_id = $params['group_id'];
        } elseif (isset($params['item']) && $params['item'] != null) {
            $project_id = $params['item']->getGroupId();
        }

        return $project_id;
    }

    /**
     * @return Project|null
     */
    private function getProjectFromParams($params)
    {
        $project = null;

        $project_id = $this->getProjectIdFromParams($params);
        if ($project_id > 0) {
            $project = ProjectManager::instance()->getProject($project_id);
        }

        return $project;
    }
}
