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

require_once 'common/project/Project.class.php';

class Tracker_HomeNavPresenter {
    
    /**
     * @var Project
     */
    private $project;
    
    /**
     * @var String
     */
    private $func;
    
    /**
     * @var Array of Array
     */
    private $nav_items = array(
        array('label_key' => 'list',   'func' => ''),
        array('label_key' => 'search', 'func' => 'cross-search')
    );
    
    public function __construct(Project $project, $func='') {
        $this->project  = $project;
        $this->func     = $func;
    }
    
    public function getNavItems() {
        return array_map(array($this, 'prepareNavItem'), $this->nav_items);
    }
    
    private function prepareNavItem($nav_item) {
        $nav_item['current'] = $this->getCurrentForItem($nav_item);
        $nav_item['url']     = $this->getUrlForItem($nav_item);
        $nav_item['label']   = $this->getLabelForItem($nav_item);
        return $nav_item;
    }
    
    private function getLabelForItem($nav_item) {
        return $GLOBALS['Language']->getText('plugin_tracker_homenav', $nav_item['label_key']);
    }
    
    private function getCurrentForItem($nav_item) {
        if ($this->func == $nav_item['func']) {
            return 'current';
        }
    }
    
    private function getUrlForItem($nav_item) {
        return TRACKER_BASE_URL . '/?group_id=' . $this->project->getId() . '&func=' . $nav_item['func'];
    }
}
?>
