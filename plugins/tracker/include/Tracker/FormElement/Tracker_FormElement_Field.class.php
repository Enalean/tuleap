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

require_once('common/user/UserManager.class.php');
require_once('common/permission/PermissionsManager.class.php');
require_once('common/user/UserHelper.class.php');

/**
 * The base class for fields in trackers. From int and string to selectboxes.
 * Composite fields are excluded.
 */
abstract class Tracker_FormElement_Field extends Tracker_FormElement implements Tracker_Report_Field  {

    protected $has_errors = false;

    /**
     * Display the field value as a criteria
     * @param Tracker_ReportCriteria $criteria
     * @return string
     * @see fetchCriteria
     */
    public abstract function fetchCriteriaValue($criteria);


    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * Please override this method for specific field (if needed)
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string the value of the field for artifact_id and changeset_id, formatted for CSV
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        return $this->fetchChangesetValue($artifact_id, $changeset_id, $value);
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public abstract function fetchRawValue($value);

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     * @param Tracker_ReportCriteria $criteria
     * @return string
     */
    public abstract function getCriteriaFrom($criteria);

    /**
     * Get the "where" statement to allow search with this field
     * @param Tracker_ReportCriteria $criteria
     * @return string
     * @see getCriteriaFrom
     */
    public abstract function getCriteriaWhere($criteria);

    /**
     * Return the dao of the criteria value used with this field.
     * @return DataAccessObject
     */
    protected abstract function getCriteriaDao();

    protected $criteria_value;
    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_ReportCriteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria) {
        if (!isset($this->criteria_value)) {
            $this->criteria_value = null;
            if ($v = $this->getCriteriaDao()
                          ->searchByCriteriaId($criteria->id)
                          ->getRow()
            ) {
                $this->criteria_value = $v['value'];
            }
        }
        return $this->criteria_value;
    }

    public function setCriteriaValue($criteria_value) {
        $this->criteria_value = $criteria_value;
    }

    public function setCriteriaValueFromSOAP(Tracker_Report_Criteria $criteria, StdClass $soap_criteria_value) {
        $this->setCriteriaValue(!empty($soap_criteria_value->value) ? $soap_criteria_value->value: '');
    }

    /**
     * Format the criteria value submitted by the user for storage purpose (dao or session)
     *
     * @param mixed $value The criteria value submitted by the user
     *
     * @return mixed
     */
    public function getFormattedCriteriaValue($value) {
        return $value;
    }

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelect() {
        $R = 'R_'. $this->id;
        return "$R.value_id AS `". $this->name."`";
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom() {
        $R = 'R_'. $this->id;
        return "INNER JOIN tracker_changeset_value AS $R ON ($R.changeset_id = c.id)";
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby() {
        return '`'.$this->name.'`';
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        $R = 'R_'. $this->id;
        return "$R.value_id";
    }

    public function fetchCriteria(Tracker_Report_Criteria $criteria) {
        $html = '';
        if ($this->criteriaCanBeAdvanced()) {
            $html .= '<table cellpadding="0" cellspacing="0"><tbody><tr><td>';
            $html .= $GLOBALS['HTML']->getImage('ic/toggle_'. ($criteria->is_advanced ? 'minus' : 'plus' ) .'.png',
                                                array('class' => 'tracker_report_criteria_advanced_toggle')
            );
            $html .= '</td><td>';
        }
        $html .= '<label for="tracker_report_criteria_'. $this->id .'" title="#'.$this->id.'">'. $this->getLabel();
        $html .= '<input type="hidden" id="tracker_report_criteria_'. $this->id .'_parent" value="'. $this->parent_id .'" />';
        $html .= '</label>';

        $html .= '<br />';
        if ($this->criteriaCanBeAdvanced()) {
            $html .=  '<div class="tracker_report_criteria">';
        }
        $html .= $this->fetchCriteriaValue($criteria);
        if ($this->criteriaCanBeAdvanced()) {
            $html .= '</div></td></tr></tbody></table>';
        }
        $html .= $this->fetchCriteriaAdditionnalInfo($criteria);
        return $html;
    }

    /**
     * Return the fieldset of this field
     * @return Tracker_FormElement_FieldSet
     */
    public function getParent() {
        return Tracker_FormElementFactory::instance()->getFieldByid($this->parent_id);
    }

    /**
     * Add some additionnal information beside the criteria.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchCriteriaAdditionnalInfo($criteria) {
        return '';
    }

    /**
     * Add some additionnal information beside the field in the artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @param $value the changeset value
     *
     * @return string
     */
    public function fetchArtifactAdditionnalInfo($value, $submitted_values = null) {
        return '';
    }

    /**
     * Add some additionnal information beside the field in the submit new artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchSubmitAdditionnalInfo() {
        return '';
    }

    /**
     * Remove a changeset value corresponding to field an id
     *
     * @param Integer $changeset_value_id
     *
     * @return Boolean
     */
    public function deleteChangesetValue($changeset_value_id) {
        return $this->getValueDao()->delete($changeset_value_id);
    }

    /**
     * Delete the criteria value
     * @param Criteria $criteria the corresponding criteria
     */
    public function deleteCriteriaValue($criteria) {
        $this->getCriteriaDao()->delete($criteria->report->id, $criteria->id);
        return $this;
    }

    /**
     * Update the criteria value
     * @param Criteria $criteria
     * @param mixed $value
     */
    public function updateCriteriaValue($criteria, $value) {
        $this->getCriteriaDao()->save($criteria->id, $value);
    }

    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced() {
        return false;
    }

    /**
     * Fetch sql snippets needed to compute aggregate functions on this field.
     *
     * @param array $functions The needed function. @see getAggregateFunctions
     *
     * @return array of the form array('same_query' => string(sql snippets), 'separate' => array(sql snippets))
     *               example:
     *               array(
     *                   'same_query'       => "AVG(R2_1234.value) AS velocity_AVG, STD(R2_1234.value) AS velocity_AVG",
     *                   'separate_queries' => array(
     *                       array(
     *                           'function' => 'COUNT_GRBY',
     *                           'select'   => "R2_1234.value AS label, count(*) AS value",
     *                           'group_by' => "R2_1234.value",
     *                       ),
     *                       //...
     *                   )
     *              )
     *
     *              Same query handle all queries that can be run concurrently in one query. Example:
     *               - numeric: avg, count, min, max, std, sum
     *               - selectbox: count
     *              Separate queries handle all queries that must be run spearately on their own. Example:
     *               - numeric: count group by
     *               - selectbox: count group by
     *               - multiselectbox: all (else it breaks other computations)
     */
    public function getQuerySelectAggregate($functions) {
        return false;
    }
    public function getQueryFromAggregate() {
        return $this->getQueryFrom();
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions() {
        return array();
    }

    /**
     * Get the html code to display the field for the given artifact
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string html
     */
    public function fetchArtifact(Tracker_Artifact $artifact, $submitted_values = array()) {
        if ($this->userCanUpdate()) {
            $value       = $artifact->getLastChangeset()->getValue($this);
            $html_value  = $this->fetchArtifactValue($artifact, $value, $submitted_values);
            $html_value .= $this->fetchArtifactAdditionnalInfo($value, $submitted_values);
            return $this->fetchArtifactField($artifact, $html_value);
        }
        return $this->fetchArtifactReadOnly($artifact);
    }

    /**
     * Get the html code to display the field for the given artifact in read only mode
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string html
     */
    public function fetchArtifactReadOnly(Tracker_Artifact $artifact) {
        $value       = $artifact->getLastChangeset()->getValue($this);
        $html_value  = $this->fetchArtifactValueReadOnly($artifact, $value);
        $html_value .= $this->fetchArtifactAdditionnalInfo($value);
        return $this->fetchArtifactField($artifact, $html_value);
    }

    /**
     * @param Tracker_Artifact $artifact   the artifact
     * @param string           $html_value in html
     *
     * @return string html
     */
    private function fetchArtifactField(Tracker_Artifact $artifact, $html_value) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanRead()) {
            $required = $this->required ? ' <span class="highlight">*</span>' : '';
            $html .= '<div class="tracker_artifact_field '. ($this->has_errors ? 'has_errors' : '') .'">';
            $html .= '<label id="tracker_artifact_'. $this->id .'" for="tracker_artifact_'. $this->id .'" title="'. $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) .'" class="tracker_formelement_label">'.  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . $required .'</label>';
            $html .= '<br />';
            $html .= $html_value;
            $html .= '</div>';
        }
        return $html;
    }

    /**
     *
     * @param Tracker_Artifact $artifact
     * @return <type>
     */
    public function fetchMailArtifact($recipient, Tracker_Artifact $artifact, $format='text', $ignore_perms=false) {
        $output = '';
        if ( $ignore_perms || $this->userCanRead($recipient) ) {
            $value = $artifact->getLastChangeset()->getValue($this);

            if ($format =='text') {
                $output .= ' * ';
                $output .= $this->getLabel();
                $output .= ' : ';
                $output .= $this->fetchMailArtifactValue($artifact, $value, $format);
            } else {
                $hp = Codendi_HTMLPurifier::instance();
                $output .= '<div class="tracker_artifact_field '. ($this->has_errors ? 'has_errors' : '') .'">';
                $output .= '<label id="tracker_artifact_'. $this->id .'" for="tracker_artifact_'. $this->id .'" title="'. $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) .'" class="tracker_formelement_label">'.  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . '</label>';
                $output .= '<br />';
                $output .= $this->fetchMailArtifactValue($artifact, $value, $format);
                $output .= '</div>';
            }
        }
        return $output;
    }



    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit($submitted_values = array()) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanSubmit()) {
            $required = $this->required ? ' <span class="highlight">*</span>' : '';
            $html .= '<div class="tracker_artifact_field '. ($this->has_errors ? 'has_errors' : '') .'">';
            $html .= '<label for="tracker_artifact_'. $this->id .'" title="'. $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) .'"  class="tracker_formelement_label">'.  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . $required .'</label>';

            $html .= '<br />';
            $html .= $this->fetchSubmitValue($submitted_values);
            $html .= $this->fetchSubmitAdditionnalInfo();
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmitMasschange() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanSubmit()) {
            $required = $this->required ? ' <span class="highlight">*</span>' : '';
            $html .= '<div class="tracker_artifact_field '. ($this->has_errors ? 'has_errors' : '') .'">';
            $html .= '<label for="tracker_artifact_'. $this->id .'" title="'. $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) .'"  class="tracker_formelement_label">'.  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . $required .'</label>';

            $html .= '<br />';
            $html .= $this->fetchSubmitValueMasschange();
            $html .= $this->fetchSubmitAdditionnalInfo();
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Get the html code to display the field in a tooltip
     * @param Tracker_Artifact $artifact
     * @return string html
     */
    public function fetchTooltip($artifact) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanRead()) {
            $html .= '<tr valign="top"><td>';
            $html .= '<label style="font-weight:bold">'.  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) .':</label>';
            $html .= '</td><td>';
            $value = $artifact->getLastChangeset()->getValue($this);
            $html .= $this->fetchTooltipValue($artifact, $value);
            $html .= '</td></tr>';
        }
        return $html;
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
    protected abstract function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array());

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public abstract function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null);

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected abstract function fetchSubmitValue();

    /**
     * Return a value from user submitted request (if any) or from default value (if any)
     *
     * @param mixed $submitted_values
     *
     * @return mixed
     */
    protected function getValueFromSubmitOrDefault($submitted_values) {
        $value = '';
        if (isset($submitted_values[$this->getId()])) {
            $value = $submitted_values[$this->getId()];
        } else if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        return $value;
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected abstract function fetchSubmitValueMasschange();

    /**
     * Fetch the html code to display the field value in tooltip
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     * @return string
     */
    protected abstract function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null);

    /**
     * Fetch the html code to display the field value in card
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string
     */
    public function fetchCardValue(Tracker_Artifact $artifact) {
        return $this->fetchTooltipValue($artifact, $artifact->getLastChangeset()->getValue($this));
    }

    /**
     * Fetch the html code to display the field in card
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string
     */
    public function fetchCard(Tracker_Artifact $artifact) {
        $html  = null;
        $value = $this->fetchCardValue($artifact);
        $html .= '<tr><td>'. $this->getLabel().':</td>';
        $html .= '<td class="valueOf_'. $this->getName() .'" data-field-id="'.$this->getId().'">'. $value .'</td></tr>';
        return $html;
    }

    /**
     * Get the value corresponding to the $value_id
     * @param int $value_id
     * @return array
     */
    public function getValue($value_id) {
        return $this->getValueDao()->searchById($value_id, $this->id)->getRow();
    }

    protected abstract function getValueDao();

    /**
     * Fetch the value to display changes in followups
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     * @return string
     */
    public abstract function fetchFollowUp($artifact, $from, $to);

    /**
     * Fetch the value to display changes in artifact history
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     * @return string
     */
    public function fetchHistory($artifact, $from, $to) {
        return $this->fetchFollowUp($artifact, $from, $to);
    }

    /**
     * Returns null because a Field object is not of the type FieldComposite
     *
     * @return null
     */
    public function getFields() {
        return null;
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public abstract function fetchRawValueFromChangeset($changeset);

    public function fetchAdmin($tracker) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $required = $this->required ? ' <span class="highlight">*</span>' : '';

        $html .= '<div class="tracker-admin-field" id="tracker-admin-formElements_'. $this->id .'">';
        $html .= '<div class="tracker-admin-field-controls">';
                $html .= '<a class="edit-field" href="'. $this->getAdminEditUrl() .'">'. $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit')) .'</a> ';
        if ($this->canBeUnused()) {
            $html .= '<a href="?'. http_build_query(array(
                'tracker'  => $tracker->id,
                'func'     => 'admin-formElement-remove',
                'formElement'    => $this->id,
            )) .'">'. $GLOBALS['HTML']->getImage('ic/cross.png', array('alt' => 'remove')) .'</a>';
        } else {
            $html .= '<span style="color:gray;" title="'. $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','delete_field_impossible') .'">';
            $html .= $GLOBALS['HTML']->getImage('ic/cross-disabled.png', array('alt' => 'remove'));
            $html .= '</span>';
        }
        $html .= '</div>';

        $html .= '<label title="'. $hp->purify($this->description, CODENDI_PURIFIER_LIGHT) .'" class="tracker_formelement_label">'. $this->getLabel() . $required .'</label>';
        $html .= '<br />';
        $html .= $this->fetchAdminFormElement();
        $html .= '</div>';

        return $html;
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported() {
        return false;
    }

    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     * @return boolean
     */
    public function takesTwoColumns() {
        return false;
    }

    /**
     * Fetch the "add criteria" box
     *
     * @param array $used Current used fields as criteria.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    public function fetchAddCriteria($used, $prefix = '') {
        $html = '';
        $class = 'tracker_report_add_criteria_unused';
        if (isset($used[$this->id])) {
            $class = 'tracker_report_add_criteria_used';
        }
        $html .= '<option value="'. $this->id .'" class="'. $class .'">'. $this->getLabel() .'</option>';
        return $html;
    }

    /**
     * Fetch the "add column" box in table renderer
     *
     * @param array $used Current used fields as column.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    public function fetchAddColumn($used, $prefix = '') {
        $html  = '';
        $class = 'tracker_report_table_add_column_unused';
        if (isset($used[$this->id])) {
            $class = 'tracker_report_table_add_column_used';
        }
        $html .= '<option value="'. $this->id .'" class="'. $class .'">'. $this->getLabel() .'</option>';
        return $html;
    }

    /**
     * Fetch the "add tooltip" box in admin
     *
     * @param array $used Current used fields as column.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    public function fetchAddTooltip($used, $prefix = '') {
        $html = '';
        if (!isset($used[$this->id])) {
            $html .= '<option value="'. $this->id .'">'. $this->getLabel() .'</option>';
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
     * Is the field used in semantics?
     *
     * @return boolean returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics() {
        $sm = new Tracker_SemanticManager($this->getTracker());
        return $sm->isUsedInSemantics($this);
    }

    /**
     * Is the field used in workflow?
     *
     * @return boolean returns true if the field is used in workflow, false otherwise
     */
    public function isUsedInWorkflow() {
        $wf = WorkflowFactory::instance();
        return $wf->isFieldUsedInWorkflow($this);
    }

    /**
     * Is the field used in a field dependency?
     *
     * @return boolean returns true if the field is used in field dependency, false otherwise
     */
    public function isUsedInFieldDependency() {
        $rm = new Tracker_RulesManager($this->getTracker(), Tracker_FormElementFactory::instance());
        return $rm->isUsedInFieldDependency($this);
    }

    /**
     * Is the field can be set as unused?
     * You can't set a field unused if it is used in the tracker
     * This method is to prevent tracker inconsistency
     *
     * @return boolean returns true if the field can be unused, false otherwise
     */
    public function canBeUnused() {
        // a field deletable if it not used in semantics nor in workflow
        return  ! ($this->isUsedInSemantics() || $this->isUsedInWorkflow() || $this->isUsedInFieldDependency());
    }

    /**
     * @return bool true if the field is considered to be required
     */
    public function isRequired() {
        return $this->required;
    }

    /**
     * Validate a field
     *
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $submitted_value      The submitted value
     * @param Tracker_Artifact_ChangesetValue $last_changeset_value The last changeset value of the field (give null if no old value)
     * @param boolean $is_submission true if artifact submission, false if artifact update
     *
     * @return boolean true on success or false on failure
     */
    public function validateField(Tracker_Artifact $artifact, $submitted_value, Tracker_Artifact_ChangesetValue $last_changeset_value = null, $is_submission = null) {
        $is_valid = true;
        $hasPermission = $this->userCanUpdate();
        if ($is_submission) {
            $hasPermission = $this->userCanSubmit();
        }
        if ($last_changeset_value === null && ((!is_array($submitted_value) && $submitted_value === null) || (is_array($submitted_value) && empty($submitted_value))) && $hasPermission && $this->isRequired()) {
            $is_valid = false;
            $this->setHasErrors(true);

            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'err_required', $this->getLabel(). ' ('. $this->getName() .')'));
        } else if (((!is_array($submitted_value) && $submitted_value !== null) || (is_array($submitted_value) && !empty($submitted_value))) &&  ! $hasPermission) {
            $is_valid = false;
            $this->setHasErrors(true);
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'bad_field_permission_update', $this->getLabel()));
        } else if ($submitted_value !== null && $hasPermission) {
            $is_valid = $this->isValid($artifact, $submitted_value);
        }
        return $is_valid;
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value) {
        if (($value === null || $value === '') && $this->isRequired()) {
            $this->has_errors = true;
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'err_required', $this->getLabel(). ' ('. $this->getName() .')'));
        } else {
            $this->has_errors = !$this->validate($artifact, $value);
        }
        return !$this->has_errors;
    }

    public function isEmpty($value) {
        return ($value === null || $value === '');
    }

    /**
     * @return boolean true if the field has errors. Default is false
     * @see isValid
     */
    public function hasErrors() {
        return $this->has_errors;
    }

    /**
     * Force the has_error flag for the field
     *
     * @param boolean true if the field has errors. Default is false
     *
     * @return void
     */
    public function setHasErrors($has_errors) {
        $this->has_errors = $has_errors;
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected abstract function validate(Tracker_Artifact $artifact, $value);

    /**
     * Save the value submitted by the user in the new changeset
     *
     * @param Tracker_Artifact           $artifact           The artifact
     * @param Tracker_Artifact_Changeset $old_changeset      The old changeset. null if it is the first one
     * @param int                        $new_changeset_id   The id of the new changeset
     * @param mixed                      $submitted_value    The value submitted by the user
     * @param User                       $submitter          The user who made the modification
     * @param boolean                    $is_submission      True if artifact submission, false if artifact update
     * @param boolean                    $bypass_permissions If true, permissions to update/submit the value on field is not checked
     *
     * @return bool true if success
     */
    public function saveNewChangeset(Tracker_Artifact $artifact, $old_changeset, $new_changeset_id, $submitted_value, User $submitter, $is_submission = false, $bypass_permissions = false) {
        $updated        = false;
        $save_new_value = false;
        $dao            = $this->getChangesetValueDao();

        if ($bypass_permissions) {
            $hasPermission = true;
        } else {
            $hasPermission = $this->userCanUpdate();
            //If a field is not submitable, but has a required default value, the value has to  be submitted ...
            if ($is_submission) {
                $hasPermission = $this->userCanSubmit() || (!$this->userCanSubmit() && $this->isrequired() && $this->getDefaultValue()!= null);
            }
        }

        $previous_changesetvalue = $this->getPreviousChangesetValue($old_changeset);
        if ($previous_changesetvalue) {
            if ($submitted_value === null || !$hasPermission || !$this->hasChanges($previous_changesetvalue, $submitted_value)) {
                //keep the old value
                if ($changeset_value_id = $dao->save($new_changeset_id, $this->id, 0)) {
                    $updated = $this->keepValue($artifact, $changeset_value_id, $previous_changesetvalue);
                }
            } else {
                $save_new_value = true;
            }
        } else if ($submitted_value !== null && $hasPermission) {
            $save_new_value = true;
        }

        if ($save_new_value && $this->isValid($artifact, $submitted_value)) {
            //Save the new value
            if ($changeset_value_id = $dao->save($new_changeset_id, $this->id, 1)) {
                $updated = $this->saveValue($artifact, $changeset_value_id, $submitted_value, $previous_changesetvalue);
            }
        }

        return $updated;
    }

    protected function getChangesetValueDao() {
        return new Tracker_Artifact_Changeset_ValueDao();
    }


    protected function getPreviousChangesetValue($old_changeset) {
        $previous_changesetvalue = null;
        if ($old_changeset) {
            $previous_changesetvalue = $old_changeset->getValue($this);
        }
        return $previous_changesetvalue;
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
    protected abstract function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null);

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
        return $this->getValueDao()->keep($previous_changesetvalue->getId(), $changeset_value_id);
    }

    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $old_value The data stored in the db
     * @param mixed                           $new_value May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges(Tracker_Artifact_ChangesetValue $old_value, $new_value) {
        return false;
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
    public abstract function getChangesetValue($changeset, $value_id, $has_changed);

    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public abstract function getSoapAvailableValues();

    /**
     * Get the field data for artifact submission
     *
     * @param string the soap field value
     *
     * @return mixed the field data corresponding to the soap_value for artifact submision
     */
    public function getFieldData($value) {
        // for atomic fields, the field data is the soap value (int, float, date, string, text)
        return $value;
    }

    /**
     * Get data from SOAP value in order to be saved in DB (create/update DB)
     *
     * @param stdClass $soap_value
     *
     * @return mixed
     */
    public function getFieldDataFromSoapValue(stdClass $soap_value) {
        return $this->getFieldData($soap_value->field_value->value);
    }

    /**
     * Get data from CSV value in order to be saved in DB (create/update DB)
     *
     * @param string $csv_value
     *
     * @return mixed
     */
    public function getFieldDataFromCSVValue($csv_value) {
        return $this->getFieldData($csv_value);
    }

    /**
     * Get the field data for CSV import
     *
     * @param string the CSV field value
     *
     * @return string the field data corresponding to the CSV preview value for CSV import
     */
    public function getFieldDataForCSVPreview($csv_value) {
        // for most of atomic fields, the field data is the same value (int, float, string, text)
        return $csv_value;
    }

    /**
     * Returns true if field has a default value defined, false otherwise
     *
     * @return boolean true if field has a default value defined, false otherwise
     */
    function hasDefaultValue() {
        return ($this->getProperty('default_value') !== null);
    }

    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    function getDefaultValue() {
        return $this->getProperty('default_value');
    }

    /**
     * Extract data from request
     * Some fields like files doesn't have their value submitted in POST or GET
     * Let them populate $fields_data[field_id] if needed
     *
     * @param array &$fields_data The user submitted value
     *
     * @return void
     */
    public function augmentDataFromRequest(&$fields_data) {
        //Do nothing for the majority of fields
    }

    /**
     * get the permissions for this field
     *
     * @return array
     */
    public function getPermissions() {
        if (!$this->cache_permissions) {
            $this->cache_permissions = array();
            //berk... legacy permission code... legacy db functions... berk!
            $sql="SELECT ugroup_id, permission_type
                  FROM permissions
                  WHERE permission_type LIKE 'PLUGIN_TRACKER_FIELD%'
                    AND object_id='". db_ei($this->getId()) ."'
                  ORDER BY ugroup_id";

            $res=db_query($sql);
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    $this->cache_permissions[$row['ugroup_id']][] = $row['permission_type'];
                }
            }
        }
        return $this->cache_permissions;
    }

    /**
     *
     * @param Array $formElement_data
     */
    public function afterCreate($formElement_data=array()) {
        //update the last changeset of all artifacts, and create a None value
        $tracker_id = $this->getTrackerId();
        if ( $valueDao   = $this->getValueDao() ) {
                $valueDao->createNoneValue($tracker_id, $this->id);
        }
    }

}

?>
