<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

/**
 * First class collection of Tracker_Artifact_View_View
 */
class Tracker_Artifact_View_ViewCollection
{

    /** @var Tracker_Artifact_View_View[] */
    private $views = array();

    public function add(Tracker_Artifact_View_View $view)
    {
        $this->views[$view->getIdentifier()] = $view;
    }

    public function fetchRequestedView(Codendi_Request $request)
    {
        $requested_view = $this->getRequestedView($request);

        $html  = '';
        $html .= $this->fetchTabs($requested_view);
        $html .= $requested_view->fetch();

        return $html;
    }

    /**
     * @return Tracker_Artifact_View_View
     */
    private function getRequestedView(Codendi_Request $request)
    {
        if (isset($this->views[$request->get('view')])) {
            return $this->views[$request->get('view')];
        } else {
            return current($this->views);
        }
    }

    /**
     * Display tabs to let user choose its view
     *
     * @return string html
     */
    private function fetchTabs(Tracker_Artifact_View_View $current_view)
    {
        $html  = '';
        $html .= '<ul class="nav nav-tabs tracker-artifact-nav">';
        foreach ($this->views as $view) {
            $class = '';
            if ($view == $current_view) {
                $class = 'active';
            }
            $html .= '<li class="' . $class . '">
                <a href="' . $view->getURL() . '" data-test="' . $view->getIdentifier() . '">' . $view->getTitle() . '</a>
            </li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
