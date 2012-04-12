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

require_once 'dao/Tracker_FormElement_Field_BurndownDao.class.php';
require_once 'Tracker_FormElement_Field_ReadOnly.class.php';

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
        
        
        $html .= '<img src="'. TRACKER_BASE_URL .'/?formElement='.$this->getId().'&func='.self::FUNC_SHOW_BURNDOWN.'" />';
        return $html;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        switch ($request->get('func')) {
            case self::FUNC_SHOW_BURNDOWN:
                //$artifact = Tracker_ArtifactFactory::instance()->getArtifactById(5261);
                //$this->fetchBurndownImage($artifact);
                break;
            default:
                parent::process($layout, $request, $current_user);
        }
    }
    
    
    
    public function fetchBurndownImage(Tracker_Artifact $artifact) {
        $remaining_effort = $this->getRemainingEffortEvolution($artifact);
        //var_dump($remaining_effort);
        $graph = $this->buildGraph($remaining_effort, 10);
        $graph->stroke();
        //header('Content-type: image/png');
        //readfile(TRACKER_BASE_DIR.'/../www/images/please-configure-your-burndown.png');
    }
    
    public function getBurndownDao() {
        return new Tracker_FormElement_Field_BurndownDao();
    }
    
    private function getArtifactLinkField(Tracker_Artifact $source_artifact) {
        $artifact_link_field = $this->getFormElementFactory()->getUsedArtifactLinkFields($source_artifact->getTracker());
        if (count($artifact_link_field) > 0) {
            return $artifact_link_field[0];
        }
    }

    // TODO: filter hierarchy
    private function getLinkedArtifacts(Tracker_Artifact $source_artifact) {
        $artifact_link_field = $this->getArtifactLinkField($source_artifact);
        $linked_artifacts = $artifact_link_field->getLinkedArtifacts($source_artifact->getLastChangeset());
        return $linked_artifacts;
    }
    
    public function getRemainingEffortEvolution(Tracker_Artifact $artifact) {
        $minday = 0;
        $maxday = 0;
        
        $artifact_ids = array();
        
        $remaining_effort = array();
        $linked_artifacts = $this->getLinkedArtifacts($artifact);
        if (count($linked_artifacts) > 0) {
            $burndown_dao = $this->getBurndownDao();
            $artifact_ids_by_tracker = array();
            
            foreach($linked_artifacts as $linked_artifact) {
                $tracker_id  = $linked_artifact->getTracker()->getId();
                $artifact_id = $linked_artifact->getId();
                $artifact_ids_by_tracker[$tracker_id][] = $artifact_id;
                $artifact_ids[]                         = $artifact_id;
            }
            
            foreach ($artifact_ids_by_tracker as $tracker_id => $artifact_ids) {
                $form_element_factory = $this->getFormElementFactory();
                $effort_field         = $form_element_factory->getFormElementByName($tracker_id, self::REMAINING_EFFORT_FIELD_NAME);
            
                if ($effort_field) {
                    $effort_field_id   = $effort_field->getId();
                    $effort_field_type = $form_element_factory->getType($effort_field);
                    $dar = $burndown_dao->searchRemainingEffort($effort_field_id, $effort_field_type, $artifact_ids);
                    foreach ($dar as $row) {
                        $day   = $row['day'];
                        $id    = $row['id'];
                        $value = $row['value'];
                        
                        if (!isset($remaining_effort[$day])) {
                            $remaining_effort[$day] = array();
                        }
                        
                        $remaining_effort[$day][$id] = $value;
                        
                        $maxday = max($maxday, $day);
                        $minday = min($minday, $day);
                    }
                }
            }
        }
        
        $start_date = mktime(0,0,0,4,11,2012);
        
        $day = 24 * 60 * 60;
        $start_date = round($start_date / $day);
        
        return $this->getComputedData($remaining_effort, $artifact_ids, $start_date, $minday, $maxday);
        
    }
    
    private function getComputedData($dbdata, $artifact_ids, $start_date, $minday, $maxday) {
        /*$dbdata = array();
        $minday = 0;
        $maxday = 0;
        while ($d = db_fetch_array($res)) {
            if (!isset($dbdata[$d['day']])) {
                $dbdata[$d['day']] = array();
            }
            $dbdata[$d['day']][$d['id']] = $d['value'];
            if ($d['day'] > $maxday)
                $maxday = $d['day'];
            if ($d['day'] < $minday)
                $minday = $d['day'];
        }*/
        $data   = array();
        for ($day = $start_date; $day <= $maxday; $day++) {
            if (!isset($data[$start_date])) {
                $data[$start_date] = array();
            }
        }
        // We assume here that SQL returns effort value order by changeset_id ASC
        // so we only keep the last value (possible to change effort several times a day)

        foreach ($artifact_ids as $aid) {
            for ($day = $minday; $day <= $maxday; $day++) {
                if ($day < $start_date) {
                    if (isset($dbdata[$day][$aid])) {
                        $data[$start_date][$aid] = $dbdata[$day][$aid];
                    }
                } else if ($day == $start_date) {
                    if (isset($dbdata[$day][$aid])) {
                        $data[$day][$aid] = $dbdata[$day][$aid];
                    } else {
                        $data[$day][$aid] = 0;
                    }
                } else {
                    if (isset($dbdata[$day][$aid])) {
                        $data[$day][$aid] = $dbdata[$day][$aid];
                    } else {
                        // No update this day: get value from previous day
                        $data[$day][$aid] = $data[$day - 1][$aid];
                    }
                }
            }
        }
        return $data;
    }
    
        /**
     * @return Chart
     */
    public function buildGraph($remaining_effort, $duration) {
        $this->title  = "Burndown";
        $this->width  = 640;
        $this->height = 480;
        $this->description = "";
        
        $this->graph = new Chart($this->width,$this->height);
        $this->graph->SetScale("datlin");
        
        // title setup
        $this->graph->title->Set($this->title);
        
        //description setup
        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);
        
        // order this->data by date asc
        ksort($remaining_effort);
        
        // Initial estimation line: a * x + b
        // where b is the sum of effort for the first day
        //       x is the number of days (starting from 0 to duration
        //       a is the slope of the line equals -b/duration (burn down goes down)
        
        
        // Build data for initial estimation
        list($first_day, $b) = each($remaining_effort);
        $b = array_sum($b);
        $day = 24 * 60 * 60;
        $start_of_sprint = $first_day;
        $a = - $b / $duration;
        $data_initial_estimation = array();
        $dates = array();
        //$end_of_weeks = array();
        $data = array();
        $previous = $b; // init with sum of effort for the first day
        // for each day
        for ($x = 0 ; $x <= $duration ; ++$x) {
            $data_initial_estimation[] = $a * $x  + $b;
            $timestamp_current_day = ($start_of_sprint + $x) * $day;
            $human_dates[] = date('M-d', $timestamp_current_day);
            if (isset($remaining_effort[$start_of_sprint + $x])) {
                $nb = array_sum($remaining_effort[$start_of_sprint + $x]);
            } else {
                if ($x - 1 < count($remaining_effort) - 1) {
                    $nb = $previous;
                } else {
                    $nb = null;
                }
            }
            $data[] = $nb;
            $previous = $nb;
            //$end_of_weeks[] = date('N', $timestamp_current_day) == 7 ? 1 : 0;
        }
        $this->graph->xaxis->SetTickLabels($human_dates);
        /*
        foreach($end_of_weeks as $i => $w) {
            if ($w) {
                $vline = new PlotLine(VERTICAL, $i, "gray9", 1);
                $this->graph->Add($vline);
            }
        }
        */
        foreach($remaining_effort as $d) {
            
        }
        $colors = $this->graph->getThemedColors();
        
        $current = new LinePlot($data);
        $current->SetColor($colors[1].':0.7');
        $current->SetWeight(2);
        $current->SetLegend('Remaining effort');
        $current->mark->SetType(MARK_FILLEDCIRCLE);
        $current->mark->SetColor($colors[1].':0.7');
        $current->mark->SetFillColor($colors[1]);
        $current->mark->SetSize(3);
        $this->graph->Add($current);
       
        //Add "initial" after current so it is on top
        $initial = new LinePlot($data_initial_estimation);
        $initial->SetColor($colors[0].':1.25');
        //$initial->SetStyle('dashed');
        $initial->SetLegend('Ideal Burndown');
        $this->graph->Add($initial);
        
        return $this->graph;
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
        return $tracker->hasFormElementWithNameAndType('remaining_effort', array('int', 'float'));
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
