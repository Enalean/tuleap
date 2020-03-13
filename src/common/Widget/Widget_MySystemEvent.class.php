<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Widget_MySystemEvent extends Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('mysystemevent');
    }

    /**
     * Get the title of the widget.
     * @return string
     */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('admin_main', 'system_event');
    }

    /**
     * Compute the content of the widget
     * @return string html
     */
    public function getContent()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $se = SystemEventManager::instance();
        $content = '';
        $content .= $se->fetchLastTenEventsStatusWidget();
        $content .= '<div style="text-align:center"><a href="/admin/system_events/">[ ' . $GLOBALS['Language']->getText('global', 'more') . ' ]</a></div>';
        return $content;
    }

    /**
     * Says if the content of the widget can be displayed through an ajax call
     * If true, then the dashboard will be rendered faster but the page will be a little bit crappy until full load.
     * @return bool
     */
    public function isAjax()
    {
        return true;
    }

     /**
     * Get the description of the widget
     * @return string html
     */
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_my_system_event', 'description');
    }
}
