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

class Cardwall_PaneInfo extends AgileDashboard_PaneInfo {
    const IDENTIFIER = 'cardwall';

    /**
     * @var string
     */
    private $plugin_theme_path;

    public function __construct($plugin_theme_path) {
        $this->plugin_theme_path = $plugin_theme_path;
    }

    public function getUri() {
        return parent::getUri().'&pane='.$this->getIdentifier();
    }

    public function getUriForMilestone(Planning_Milestone $milestone) {
        return parent::getUriForMilestone($milestone).'&pane='.$this->getIdentifier();
    }

    /**
     * @see AgileDashboard_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    /**
     * @see AgileDashboard_Pane::getTitle()
     */
    public function getTitle() {
        return 'Card Wall';
    }

    /**
     * @see AgileDashboard_Pane::getIcon()
     */
    public function getIcon() {
        return $this->plugin_theme_path .'/images/ic/sticky-note-pin.png';
    }

    /**
     * @see AgileDashboard_Pane::getIconTitle()
     */
    public function getIconTitle() {
        return $GLOBALS['Language']->getText('plugin_cardwall', 'access_cardwall');
    }

}

?>
