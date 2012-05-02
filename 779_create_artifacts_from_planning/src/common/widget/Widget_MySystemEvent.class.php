<?php
/**
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
require_once('common/system_event/SystemEventManager.class.php');
class Widget_MySystemEvent extends Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('mysystemevent');
    }
    
    /**
     * Get the title of the widget.
     * @return string
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('admin_main', 'system_event');
    }
    
    /**
     * Compute the content of the widget
     * @return string html
     */
    public function getContent() {
        $hp = Codendi_HTMLPurifier::instance();
        $se = SystemEventManager::instance();
        $content = '';
        $content .= $se->fetchLastEventsStatus(0, 10);
        $content .= '<div style="text-align:center"><a href="/admin/system_events/">[ '. $GLOBALS['Language']->getText('global', 'more') .' ]</a></div>';
        return $content;
    }
    
    /**
     * Says if the content of the widget can be displayed through an ajax call
     * If true, then the dashboard will be rendered faster but the page will be a little bit crappy until full load.
     * @return boolean
     */
    public function isAjax() {
        return true;
    }
    
     /**
     * Get the description of the widget
     * @return string html
     */
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_my_system_event','description');
    }
}

?>
