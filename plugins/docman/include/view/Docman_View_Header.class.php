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

/* abstract */ class Docman_View_Header extends Docman_View_View
{
    public function _header($params)
    {
        if (! headers_sent()) {
            header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        }

        if (isset($params['title'])) {
            $htmlParams['title'] = $params['title'];
        } else {
            $htmlParams['title'] = $this->_getTitle($params);
        }

        $htmlParams = array_merge($htmlParams, $this->_getAdditionalHtmlParams($params));
        if (isset($params['docman'])) {
            $htmlParams['service_name'] = $params['docman']->plugin->getServiceShortname();
        }

        if (isset($params['pv']) && $params['pv'] > 0) {
            $htmlParams['pv'] = $params['pv'];
            $GLOBALS['HTML']->pv_header($htmlParams);
        } else {
            $project = $this->getProjectFromParams($params);
            if ($project) {
                $service = $project->getService($htmlParams['service_name']);
                \assert($service instanceof Tuleap\Docman\ServiceDocman);
                if ($service) {
                    $service->displayHeader($htmlParams['title'], $this->getBreadcrumbs($params, $project, $service), $this->getToolbar($params));
                } else {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'Service unavailable in project');
                    $GLOBALS['Response']->redirect('/');
                }
            } else {
                $GLOBALS['HTML']->includeCalendarScripts();
                $htmlParams['body_class'] = ['docman-body'];
                site_header($htmlParams);
            }
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

    /* protected */ public function _getAdditionalHtmlParams($params)
    {
        return [];
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
