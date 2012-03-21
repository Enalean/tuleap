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


class Planning_Presenter {
    
    public $planning_name;
    public $destination_id;
    public $destination_title;
    private $artifact = false;
    private $content_view ;
    
    public function __construct(Planning $planning, Tracker_CrossSearch_SearchContentView $content_view, Tracker_Artifact $artifact = null) {
        $this->planning_name     = $planning->getName();
        if ($artifact) {
            $this->destination_id    = $artifact->getId();
            $this->destination_title = $artifact->fetchTitle();
        }
        $this->artifact = $artifact;
        $this->content_view = $content_view;
    }
    
    function hasArtifact() {
        return $this->artifact !== null;
    }
    
    function getLinkedItems() {
        $linked_items = $this->artifact->getLinkedArtifacts();
        if (! $linked_items) {
            $linked_items = array();
        }
        return $linked_items;

    }
    
    public function fetchSearchContent() {
        return $this->content_view->fetch();
    }
    
    public function pleaseChoose() {
        return $GLOBALS['Language']->getText('global', 'please_choose_dashed');
    }
}

?>
