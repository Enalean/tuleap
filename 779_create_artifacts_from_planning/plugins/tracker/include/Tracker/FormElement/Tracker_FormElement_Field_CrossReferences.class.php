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

require_once('Tracker_FormElement_Field_ReadOnly.class.php');
require_once('common/dao/CrossReferenceDao.class.php');
require_once('common/reference/CrossReferenceFactory.class.php');

class Tracker_FormElement_Field_CrossReferences extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly {
    
    public $default_properties = array();
    
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                //TODO: move this in a dao
                $v = $criteria_value;
                if (is_numeric($v)) {
                    $cond = '= '. (int)$v;
                } else {
                    $cond = "= '$v'"; //todo quotesmart + rlike
                }
                $a = 'A_'. $this->id; 
                return " INNER JOIN cross_references AS $a 
                         ON (artifact.id = $a.source_id AND $a.source_type = '".Tracker_Artifact::REFERENCE_NATURE."' AND $a.target_id $cond
                             OR
                             artifact.id = $a.target_id AND $a.target_type = '".Tracker_Artifact::REFERENCE_NATURE."' AND $a.source_id $cond
                         )
                ";
            }
        }
    }
    
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        return "$R1.id AS `". $this->name . "`";
    }
    
    public function getQueryFrom() {
        $R1 = 'R1_'. $this->id;
        return " LEFT JOIN (
                     cross_references AS $R1
                 ) ON (a.id = $R1.source_id AND $R1.source_type = '".Tracker_Artifact::REFERENCE_NATURE."' 
                       OR
                       a.id = $R1.target_id AND $R1.target_type = '".Tracker_Artifact::REFERENCE_NATURE."'
                 )
        ";
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $html = '';
        $crossref_fact= new CrossReferenceFactory($artifact_id, Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences()) {
            $html .= $crossref_fact->getHTMLDisplayCrossRefs($with_links = true, $condensed = true);
        } else {
            $html .= '';
        }
        return $html;
    }
    
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        // TODO: implement it if required
        $html = '';
        return $html;
    }
    
    /**
     * Display the field value as a criteria
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria) {
        $value = $this->getCriteriaValue($criteria);
        if (!$value) {
            $value = '';
        }
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="text" name="criteria['. $this->id .']" value="'. $hp->purify($this->getCriteriaValue($criteria), CODENDI_PURIFIER_CONVERT_HTML) .'" />';
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return 'references raw value';
    }
    
    /**
     * Return the dao of the criteria value used with this field.
     * @return DataAccessObject
     */
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_Text_ValueDao();
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue() {
        return '';
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        return '';
    }

    protected function getValueDao() {
        return new CrossReferenceDao();
    }

    public function afterCreate() {
       
    }

    /**
     * Fetch the value to display changes in followups
     *
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     *
     * @return string
     */
    public function fetchFollowUp($artifact, $from, $to) {
        //don't display anything in follow-up
        return '';
    }
    
    /**
     * Fetch the value in a specific changeset
     *
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        //Nothing special to say here
        return '';
    }
    
    /**
     * Save the value and return the id
     *
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
       //The field is ReadOnly
       return null;
    }
    
    /**
     * Keep the value 
     * 
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value 
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue) {
        //The field is ReadOnly
        return null;
    }
    
    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        return null;
    }
    
    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() {
        return null;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
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
        $crossref_fact= new CrossReferenceFactory($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences()) {
            $html .= $crossref_fact->getHTMLDisplayCrossRefs();
        } else {
            $html .= '<div>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'ref_list_empty') .'</div>';
        }
        return $html;
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

        $crf  = new CrossReferenceFactory($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crf->fetchDatas();

        switch ($format) {
            case 'html':
                if ($crf->getNbReferences()) {
                    $output .= $crf->getHTMLDisplayCrossRefs(true, false, false);
                } else {
                    $output .= '<div>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'ref_list_empty') .'</div>';
                }
                break;
            default:
                $refs = $crf->getMailCrossRefs('text');
                $src  = '';
                $tgt  = '';
                $both = '';
                $output = PHP_EOL;
                if ( !empty($refs['target']) ) {
                    foreach ( $refs['target'] as $refTgt ) {
                        $tgt .= $refTgt['ref'];
                        $tgt .= PHP_EOL;
                        $tgt .= $refTgt['url'];
                        $tgt .= PHP_EOL;
                    }                    
                    $output .= ' -> Target : '.PHP_EOL.$tgt;
                    $output .= PHP_EOL;
                }
                if ( !empty($refs['source']) ) {
                    foreach ( $refs['source'] as $refSrc ) {
                        $src .= $refSrc['ref'];
                        $src .= PHP_EOL;
                        $src .= $refSrc['url'];
                        $src .= PHP_EOL;
                    }                    
                    $output .= ' -> Source : '.PHP_EOL.$src;
                    $output .= PHP_EOL;
                }
                if ( !empty($refs['both']) ) {
                    foreach ( $refs['both'] as $refBoth ) {
                        $both .= $refBoth['ref'];
                        $both .= PHP_EOL;
                        $both .= $refBoth['url'];
                        $both .= PHP_EOL;
                    }                    
                    $output .= ' -> Both   : '.PHP_EOL.$both;
                    $output .= PHP_EOL;
                }
                break;
        }
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $html .= '<div>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','display_references') .'</div>';
        return $html;
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'crossreferences_label');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'crossreferences_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/both_arrows.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/both_arrows.png');
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
        $crossref_fact= new CrossReferenceFactory($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences()) {
            $html .= $crossref_fact->getHTMLDisplayCrossRefs($with_links = false);
        } else {
            $html .= '<div>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'ref_list_empty') .'</div>';
        }
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
         $html = $this->fetchSubmitValueMassChange();
         return $html;
     }
}
?>
