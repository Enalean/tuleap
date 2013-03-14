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

require_once TRACKER_BASE_DIR .'/Tracker/CardPresenter.class.php';

class Planning_ItemPresenter implements Tracker_CardPresenter {
    
    /**
     * @var Planning_Item
     */
    private $planning_item;
    
    /**
     * @var string
     */
    private $css_classes;
    
    /**
     * @var Tracker_CardFields
     */
    private $card_fields;

    /** @var string */
    private $accent_color;
    
    /**
     * @param Planning_Item $planning_item The planning item to be presented.
     * @param string        $card_fields   The fields of the card
     * @param string        $accent_color  The accent color
     * @param string        $css_classes   The space-separated CSS classes to add to the main item HTML tag.
     */
    public function __construct(Planning_Item $planning_item, Tracker_CardFields $card_fields, $accent_color, $css_classes = '') {
        $this->planning_item = $planning_item;
        $this->css_classes   = $css_classes;
        $this->details       = $GLOBALS['Language']->getText('plugin_cardwall', 'details');
        $this->card_fields   = $card_fields;
        $this->accent_color  = $accent_color;
    }
    
    public function getId() {
        return $this->planning_item->getId();
    }
    
    public function getTitle() {
        return $this->planning_item->getTitle();
    }
    
    public function getUrl() {
        return $this->planning_item->getEditUri();
    }
    
    public function getXRef() {
        return $this->planning_item->getXRef();
    }
    
    public function getEditUrl() {
        return $this->getUrl();
    }
    
    public function getArtifactId() {
        return $this->planning_item->getId();
    }
    
    public function getAncestorId() {
        return $this->planning_item->getAncestorId();
    }
    
    public function getArtifact() {
        return $this->planning_item->getArtifact();
    }
    
    public function getEditLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
    
    public function getCssClasses() {
        return trim($this->css_classes.' '.$this->getPlanningDraggableClass());
    }
    
    private function getPlanningDraggableClass() {
        if ($this->planning_item->isPlannifiable()) {
            return 'planning-draggable';
        }
    }
    
    public function allowedChildrenTypes() {
        return $this->planning_item->getAllowedChildrenTypes();
    }
    
    public function getFields() {
        $diplayed_fields_presenter = array();
        $artifact = $this->getArtifact();
        $displayed_fields = $this->card_fields->getFields($artifact);
        
        foreach ($displayed_fields as $displayed_field) {
            $diplayed_fields_presenter[] = new Planning_ItemFieldPresenter($displayed_field, $this->getArtifact());
        }
        return $diplayed_fields_presenter;
    }
    
    public function hasFields() {
        return count($this->getFields()) > 0;
    }

    /**
     * @see Tracker_CardPresenter::getAccentColor()
     */
    public function getAccentColor() {
        return $this->accent_color;
    }
}
?>
