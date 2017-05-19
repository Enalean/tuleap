<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Widget.class.php');

/**
* Widget_ProjectClassification
*/
class Widget_ProjectClassification extends Widget {
    public function __construct() {
        $this->Widget('projectclassification');
    }
    public function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','project_classification');
    }
    public function getContent() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $html = '';
        if ($GLOBALS['sys_use_trove'] != 0) {
            ob_start();
            trove_getcatlisting($group_id, 0, 1);
            $html = ob_get_clean();
        }

        return $html;
    }

    public function canBeUsedByProject(&$project) {
        return true;
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_classification','description');
    }
}
?>
