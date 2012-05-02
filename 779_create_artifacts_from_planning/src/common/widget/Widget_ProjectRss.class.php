<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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

require_once('Widget_Rss.class.php');
require_once('Widget.class.php');

/**
* Widget_TwitterFollow
* 
* Allow to follow a twitter user
* 
*/
class Widget_ProjectRss extends Widget_Rss {
    function Widget_ProjectRss() {
        $request =& HTTPRequest::instance();
        $this->Widget_Rss('projectrss', $request->get('group_id'), WidgetLayoutManager::OWNER_TYPE_GROUP);
    }
    function canBeUsedByProject(&$project) {
        return true;
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_rss','description');
    }
}
?>
