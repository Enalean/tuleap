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
require_once 'CardFieldPresenter.class.php';

class Cardwall_CardPresenter implements Tracker_CardPresenter{
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var Array
     */
    private $displayed_fields;

    public function __construct(Tracker_Artifact $artifact) {
        $this->artifact = $artifact;
        $this->displayed_fields   = array(Tracker_CardPresenter::REMAINING_EFFORT_FIELD_NAME,
                                          Tracker_CardPresenter::ASSIGNED_TO_FIELD_NAME,
                                          Tracker_CardPresenter::IMPEDIMENT_FIELD_NAME);
        $this->details  = $GLOBALS['Language']->getText('plugin_cardwall', 'details');
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
        $user                      = UserManager::instance()->getCurrentUser();
        $form_element_factory      = Tracker_FormElementFactory::instance();
        $tracker_id                = $this->artifact->getTracker()->getId();
        
        foreach ($this->displayed_fields as $diplayed_field_name) {
            $field = $form_element_factory->getUsedFieldByNameForUser(
                        $tracker_id,
                        $diplayed_field_name,
                        $user);
            if ($field) {
                $diplayed_fields_presenter[] = new Cardwall_CardFieldPresenter($field, $this->artifact);
            }
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
     * @see Tracker_CardPresenter
     */
    public function allowedChildrenTypes() {
        return $this->artifact->getAllowedChildrenTypes();
    }
}
?>
