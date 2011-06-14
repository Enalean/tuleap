<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/widget/Widget.class.php';

class Timeline_Widget_User extends Widget {

    /**
     * Constructor
     *
     * @param Plugin $plugin The plugin
     */
    function __construct(Plugin $plugin) {
        parent::__construct('timeline_user');
        $this->_plugin = $plugin;
    }

    /**
     * Widget title
     *
     * @see src/common/widget/Widget#getTitle()
     * @return String
     */
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_timeline', 'widget_user_title');
    }

    /**
     * Widget description
     *
     * @see src/common/widget/Widget#getDescription()
     *
     * @return String
     */
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_timeline','widget_user_description');
    }

    /**
     * Tell if a widget can by used by a project
     *
     * @param Project $project
     */
    function canBeUsedByProject(Project $project) {
        return false;
    }

    function isAjax() {
        return true;
    }

    /**
     * Widget content
     *
     * @see src/common/widget/Widget#getContent()
     * @return String
     */
    public function getContent() {
        return 'Stuff';
    }

}

?>