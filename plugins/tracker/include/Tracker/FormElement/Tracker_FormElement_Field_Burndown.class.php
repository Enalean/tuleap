<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'Tracker_FormElement_Field_ReadOnly.class.php';
require_once dirname(__FILE__).'/../Chart/Data/LinkedArtifacts.class.php';
require_once dirname(__FILE__).'/../Chart/Burndown.class.php';

class Tracker_FormElement_Field_Burndown extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly {
    
    /**
     * Request parameter to display burndown image 
     */
    const FUNC_SHOW_BURNDOWN          = 'show_burndown';
    const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';
    
    public $default_properties = array();
    
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    
    /**
     * Returns the previously injected factory (e.g. in tests), or a new
     * instance (e.g. in production).
     * 
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory() {
        if ($this->hierarchy_factory == null) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::build();
        }
        return $this->hierarchy_factory;
    }
    
    /**
     * Provides a way to inject the HierarchyFactory, since it cannot be done
     * in the constructor.
     * 
     * @param Tracker_HierarchyFactory $hierarchy_factory 
     */
    public function setHierarchyFactory($hierarchy_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
    }
    
    public function getCriteriaFrom($criteria) {
    }
    
    public function getCriteriaWhere($criteria) {
    }
    
    public function getQuerySelect() {
    }
    
    public function getQueryFrom() {
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
    }
    
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
    }
    
    public function fetchCriteriaValue($criteria) {
    }

    public function fetchRawValue($value) {
    }
    
    protected function getCriteriaDao() {
    }
    
    protected function fetchSubmitValue() {
    }

    protected function fetchSubmitValueMasschange() {
    }

    protected function getValueDao() {
    }

    public function afterCreate() {
    }

    public function fetchFollowUp($artifact, $from, $to) {
    }
    
    public function fetchRawValueFromChangeset($changeset) {
    }
    
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
    }
    
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue) {
    }
    
    public function getChangesetValue($changeset, $value_id, $has_changed) {
    }
    
    public function getSoapAvailableValues() {
    }

    public function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }
    
    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        //var_dump($this->getRemainingEffortEvolution($artifact));
        /*foreach ($this->getRemainingEffortEvolution($artifact) as $linked_artifact) {
            var_dump($linked_artifact->getId());
        }*/
        //$linked_artifact = $this->getLinkedArtifactIds($artifact);
        
        //$start_date_field = $this->getFormElementFactory()->getFormElementByName($artifact->getTracker()->getId(), 'start_date');
        //var_dump($artifact->getValue($start_date_field)->getTimestamp());
        
        //var_dump($this->getFormElementFactory()->getFormElementByName($artifact->getTracker()->getId(), 'start_date')->fetchArtifactValue($artifact));
        
        $url_query = http_build_query(array('formElement' => $this->getId(),
                                            'func'        => self::FUNC_SHOW_BURNDOWN,
                                            'src_aid'         => $artifact->getId()));
        
        $html .= '<img src="'. TRACKER_BASE_URL .'/?'.$url_query.'" />';
        return $html;
    }

    /**
     *
     * @param Tracker_IDisplayTrackerLayout $layout
     * @param Codendi_Request               $request
     * @param User                          $current_user 
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        switch ($request->get('func')) {
            case self::FUNC_SHOW_BURNDOWN:
                $artifact_id = $request->getValidated('src_aid', 'uint', 0);
                $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
                if (! $artifact) {
                    return false;
                }
                $this->fetchBurndownImage($artifact);
                break;
            default:
                parent::process($layout, $request, $current_user);
        }
    }
    
    
    
    public function fetchBurndownImage(Tracker_Artifact $artifact) {
        $linked_artifacts = $this->getLinkedArtifacts($artifact);
        if (count($linked_artifacts)) {
            $burndown = $this->getBurndown($linked_artifacts);
            $burndown->setStartDate($this->getBurndownStartDate($artifact));
            //$burndown->setDuration();
            //$burndown->setWidth(640);
            //$burndown->setHeight(480);
            //$burndown->setTitle("Burndown");
            //$burndown->setDescription("480");
            $burndown->display();
        }
    }
    
    protected function getBurndown($linked_artifacts) {
        $burndown_data = new Tracker_Chart_Burndown_Data_LinkedArtifacts($linked_artifacts, self::REMAINING_EFFORT_FIELD_NAME);
        return new Tracker_Chart_Burndown($burndown_data);
    }

    protected function getBurndownStartDate(Tracker_Artifact $artifact) {
        $start_date_field = $this->getFormElementFactory()->getFormElementByName($artifact->getTracker()->getId(), 'start_date');
        return $artifact->getValue($start_date_field)->getTimestamp();
    }

    /**
     *
     * @param Tracker_Artifact $source_artifact
     * 
     * @return Tracker_FormElement_Field_ArtifactLink
     */
    private function getArtifactLinkField(Tracker_Artifact $source_artifact) {
        $artifact_link_field = $this->getFormElementFactory()->getUsedArtifactLinkFields($source_artifact->getTracker());
        if (count($artifact_link_field) > 0) {
            return $artifact_link_field[0];
        }
    }

    // TODO: filter hierarchy
    public function getLinkedArtifacts(Tracker_Artifact $source_artifact) {
        $artifact_link_field = $this->getArtifactLinkField($source_artifact);
        $linked_artifacts = $artifact_link_field->getLinkedArtifacts($source_artifact->getLastChangeset());
        return $linked_artifacts;
    }
    
    /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        $output = '';
        //TODO: What to send in email?
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    public function fetchAdminFormElement() {
        $html = $this->fetchWarnings();
        $html .= '<img src="'. TRACKER_BASE_URL .'/images/fake-burndown-admin.png" />';
        return $html;
    }
    
    /**
     * Renders all the possible warnings for this field.
     * 
     * @return String
     */
    private function fetchWarnings() {
        $warnings  = '';
        $warnings .= $this->fetchMissingFieldWarning('start_date', 'date');
        $warnings .= $this->fetchMissingFieldWarning('duration', 'int');
        $warnings .= $this->fetchMissingRemainingEffortWarning();
        
        if ($warnings) {
            return '<ul class="feedback_warning">'.$warnings.'</ul>';
        }
    }
    
    /**
     * Renders a warning concerning some missing field in the tracker.
     * 
     * @param String $name
     * @param String $type
     * @return String 
     */
    private function fetchMissingFieldWarning($name, $type) {
        if (! $this->getTracker()->hasFormElementWithNameAndType($name, $type)) {
            $key     = "burndown_missing_${name}_warning";
            $warning = $GLOBALS['Language']->getText('plugin_tracker', $key);
            
            return '<li>'.$warning.'</li>';
        }
    }
    
    /**
     * Renders a warning concerning some child tracker(s) missing a remaining
     * effort field.
     * 
     * @return String
     */
    private function fetchMissingRemainingEffortWarning() {
        $tracker_links = implode(', ', $this->getLinksToChildTrackersWithoutRemainingEffort());
        
        if ($tracker_links) {
            $warning = $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_remaining_effort_warning');
            return "<li>$warning $tracker_links.</li>";
        }
    }
    
    /**
     * Returns the names of child trackers missing a remaining effort.
     * 
     * @return array of String
     */
    private function getLinksToChildTrackersWithoutRemainingEffort() {
        return array_map(array($this, 'getLinkToTracker'),
                         $this->getChildTrackersWithoutRemainingEffort());
    }
    
    /**
     * Renders a link to the given tracker.
     * 
     * @param Tracker $tracker
     * @return String
     */
    private function getLinkToTracker(Tracker $tracker) {
        $tracker_id   = $tracker->getId();
        $tracker_name = $tracker->getName();
        $tracker_url  = TRACKER_BASE_URL."/?tracker=$tracker_id&func=admin-formElements";
        
        return '<a href="'.$tracker_url.'">'.$tracker_name.'</a>';
    }
    
    /**
     * Returns the child trackers missing a remaining effort.
     * 
     * @return array of Tracker
     */
    private function getChildTrackersWithoutRemainingEffort() {
        return array_filter($this->getChildTrackers(),
                            array($this, 'missesRemainingEffort'));
    }
    
    /**
     * Returns true if the given tracker misses a remaining effort field.
     * 
     * @param Tracker $tracker
     * @return Boolean
     */
    private function missesRemainingEffort(Tracker $tracker) {
        return ! $this->hasRemainingEffort($tracker);
    }
    
    /**
     * Returns true if the given tracker has a remaining effort field.
     * 
     * @param Tracker $tracker
     * @return Boolean
     */
    private function hasRemainingEffort(Tracker $tracker) {
        return $tracker->hasFormElementWithNameAndType(self::REMAINING_EFFORT_FIELD_NAME, array('int', 'float'));
    }
    
    /**
     * Returns the children of the burndown field tracker.
     * 
     * @return array of Tracker
     */
    private function getChildTrackers() {
        return $this->getHierarchyFactory()->getChildren($this->getTrackerId());
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'burndown_label');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'burndown_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/burndown.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/burndown--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        return $html;
    }

    /**
     * Verifies the consistency of the imported Tracker
     * 
     * @return true if Tracler is ok 
     */
    public function testImport() {
        return true;
    }

    
    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param mixed            $value    data coming from the request. 
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        //No need to validate artifact id (read only for all)
        return true;
    }
    
    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
     public function fetchSubmit() {
         return '';
     }

     /**
     * Fetch the element for the submit masschange form
     *
     * @return string html
     */
     public function fetchSubmitMasschange() {
     }
}
?>
