<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class AgileDashboard_MilestonePlanningPaneInfo extends AgileDashboard_PaneInfo {
    const IDENTIFIER = 'planner';
    
    /**
     * @return string eg: 'cardwall'
     */
    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    /**
     * @return string eg: 'Card Wall'
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_pane_title');
    }
    
}

class AgileDashboard_MilestonePlanningPane extends AgileDashboard_Pane {

    private $presenter;

    public function __construct(AgileDashboard_MilestonePlanningPresenter $presenter) {
        $this->presenter = $presenter;
    }

    public function getIdentifier() {
        return AgileDashboard_MilestonePlanningPaneInfo::IDENTIFIER;
    }

    /**
     * @return string eg: '<a href="">customize</a> <table>...</table>'
     */
    public function getFullContent() {
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../../templates');
        return $renderer->renderToString('milestone-planning', $this->presenter);
    }

    /**
     * @return string eg: '<table>...</table>'
     */
    public function getMinimalContent() {
        throw new RuntimeException('I should be implemented');
    }

    /**
     * @see string eg: '/themes/common/images/ic/duck.png'
     */
    public function getIcon() {
        throw new RuntimeException('I should be implemented');
    }

    /**
     * @return string eg: 'Access to cardwall'
     */
    public function getIconTitle() {
        throw new RuntimeException('I should be implemented');
    }
}



?>
