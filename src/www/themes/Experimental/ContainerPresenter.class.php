<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Experimental_ContainerPresenter {

    /** @var array */
    private $breadcrumbs;

    /** @var array */
    private $toolbar;

    /** @var string */
    private $project_name;

    /** @var string */
    private $project_link;

    /** @var boolean */
    private $project_is_public;

    /** @var string */
    private $project_tabs;

    /** @var Feedback */
    private $feedback;

    /** @var string */
    private $feedback_content;

    private $forge_version;

    function __construct(
        $breadcrumbs,
        $toolbar,
        $project_name,
        $project_link,
        $project_is_public,
        $project_tabs,
        $feedback,
        $feedback_content,
        $forge_version
    ) {
        $this->breadcrumbs       = $breadcrumbs;
        $this->toolbar           = $toolbar;
        $this->project_name      = $project_name;
        $this->project_link      = $project_link;
        $this->project_is_public = $project_is_public;
        $this->project_tabs      = $project_tabs;
        $this->feedback          = $feedback;
        $this->feedback_content  = $feedback_content;
        $this->forge_version     = $forge_version;
    }

    public function hasBreadcrumbs() {
        return (count($this->breadcrumbs) > 0);
    }

    public function breadcrumbs() {
        return $this->breadcrumbs;
    }

    public function hasToolbar() {
        return (count($this->toolbar) > 0);
    }

    public function toolbar() {
        return implode('</li><li>', $this->toolbar);
    }

    public function hasSidebar() {
        return isset($this->project_tabs);
    }

    public function sidebar() {
        return $this->project_tabs;
    }

    public function powered_by() {
        return $GLOBALS['Language']->getText('global','powered_by').' '.$this->forge_version;
    }

    public function copyright() {
        return $GLOBALS['Language']->getText('global','copyright');
    }

    public function projectName() {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify($this->project_name);
    }

    public function projectLink() {
        return $this->project_link;
    }

    public function projectIsPublic() {
        return $this->project_is_public;
    }

    public function feedback() {
        $html  = $this->feedback->htmlContent();
        $html .= $this->feedback_content;

        return $html;
    }
}

?>
