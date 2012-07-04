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
    
    const REMAINING_EFFORT_FIELD_NAME = "remaining_effort";
    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    public function __construct(Tracker_Artifact $artifact) {
        $this->artifact           = $artifact;
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
        $value                = '';
        $user                 = UserManager::instance()->getCurrentUser();
        $field_name           = Cardwall_CardPresenter::REMAINING_EFFORT_FIELD_NAME;
        $form_element_factory = Tracker_FormElementFactory::instance();
        
        $field = $form_element_factory->getComputableFieldByNameForUser(
                $this->artifact->getTracker()->getId(),
                $field_name,
                $user
        );    
        return array(new Cardwall_CardFieldPresenter($field, $this->artifact));
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
        $hierarchy_factory = Tracker_HierarchyFactory::instance();
        return $hierarchy_factory->getChildren($this->artifact->getTracker()->getId());
    }
}
?>
