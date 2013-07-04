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

class Cardwall_CardPresenter implements Tracker_CardPresenter {

    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var Tracker_Artifact
     */
    private $parent;
    
    /**
     * @var Tracker_CardFields
     */
    private $card_fields;

    /** @var string */
    private $accent_color;

    private $swimline_id;

    public function __construct(Tracker_Artifact $artifact, Tracker_CardFields $card_fields, $accent_color, Cardwall_DisplayPreferences $display_preferences, $swimline_id, Tracker_Artifact $parent = null) {
        $this->artifact            = $artifact;
        $this->parent              = $parent;
        $this->details             = $GLOBALS['Language']->getText('plugin_cardwall', 'details');
        $this->card_fields         = $card_fields;
        $this->accent_color        = $accent_color;
        $this->display_preferences = $display_preferences;
        $this->swimline_id         = $swimline_id;
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getId() {
        return $this->artifact->getId();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getTitle() {
        return $this->artifact->getTitle();
    }
    
    public function getFields() {
        $diplayed_fields_presenter = array();
        $displayed_fields = $this->card_fields->getFields($this->getArtifact());
        
        foreach ($displayed_fields as $displayed_field) {
            $diplayed_fields_presenter[] = new Cardwall_CardFieldPresenter($displayed_field, $this->artifact, $this->display_preferences);
        }
        return $diplayed_fields_presenter;
    }
    
    public function hasFields() {
        return count($this->getFields()) > 0;
    }
    
    /**
     * @see Tracker_CardPresenter
     */
    public function getUrl() {
        return $this->artifact->getUri();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getXRef() {
        return $this->artifact->getXRef();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getEditUrl() {
        return $this->getUrl();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getArtifactId() {
        return $this->artifact->getId();
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getArtifact() {
        return $this->artifact;
    }
    
    public function getAncestorId() {
        return $this->parent ? $this->parent->getId() : 0;
    }

    public function getSwimlineId() {
        return $this->swimline_id;
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getEditLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function getCssClasses() {
        return '';
    }

    /**
     * @see Tracker_CardPresenter::getAccentColor()
     */
    public function getAccentColor() {
        return $this->accent_color;
    }

    /**
     * @see Tracker_CardPresenter
     */
    public function allowedChildrenTypes() {
        return $this->artifact->getAllowedChildrenTypes();
    }
}
?>
