<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueUnchanged;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;
use Tuleap\Tracker\FormElement\TransitionListValidator;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

abstract class Tracker_FormElement_Field_List extends Tracker_FormElement_Field implements Tracker_FormElement_Field_Shareable
{

    public const NONE_VALUE          = 100;
    public const NOT_INDICATED_VALUE = 0;

    protected $bind;

    /**
     * @return array
     */
    public function getFormElementDataForCreation($parent_id)
    {
        $form_element_data = parent::getFormElementDataForCreation($parent_id);

        if ($this->getBind()) {
            $form_element_data['bind-type'] = $this->getBind()->getType();
        }

        return $form_element_data;
    }

    /**
     * Return true if submitted value is None
     */
    abstract public function isNone($value);

    /**
     * @return Tracker_FormElement_Field_List_Bind|null
     * @psalm-ignore-nullable-return
     */
    public function getBind()
    {
        if (!$this->bind) {
            $this->bind = null;
            //retrieve the type of the bind first...
            $dao = new Tracker_FormElement_Field_ListDao();
            if ($row = $dao->searchByFieldId($this->id)->getRow()) {
                //...and build the bind
                $bind_factory = $this->getFormElementFieldListBindFactory();
                $this->bind   = $bind_factory->getBind($this, $row['bind_type']);
            }
        }
        return $this->bind;
    }

    /**
     * @return Tracker_FormElement_Field_List_BindFactory
     */
    protected function getFormElementFieldListBindFactory()
    {
        return new Tracker_FormElement_Field_List_BindFactory();
    }

    /**
     * @return array of Tracker_FormElement_Field_List_BindDecorator
     */
    public function getDecorators()
    {
        return $this->getBind()->getDecorators();
    }

    public function setBind($bind)
    {
        $this->bind = $bind;
    }

    /**
     * Duplicate a field. If the field has custom properties,
     * they should be propagated to the new one
     * @param int $from_field_id
     * @return array the mapping between old values and new ones
     */
    public function duplicate($from_field_id)
    {
        $dao = new Tracker_FormElement_Field_ListDao();
        if ($dao->duplicate($from_field_id, $this->id)) {
            $bf = new Tracker_FormElement_Field_List_BindFactory();
            return $bf->duplicate($from_field_id, $this->id);
        }
        return array();
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return false;
    }

    public function canBeUsedToSortReport()
    {
        return ! $this->isMultiple();
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria)
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            return $this->getBind()->getCriteriaFrom($this->getCriteriaValue($criteria));
        }

        return '';
    }

    /**
     * Get the "where" statement to allow search with this field
     *
     * @see getCriteriaFrom
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     */
    public function getCriteriaWhere($criteria)
    {
        return $this->getBind()->getCriteriaWhere($this->getCriteriaValue($criteria));
    }

    /**
     * Get the "select" statement to retrieve field values
     *
     * @see getQueryFrom
     *
     * @return string
     */
    public function getQuerySelect()
    {
        return $this->getBind()->getQuerySelect();
    }

    /**
     * Get the "select" statement to retrieve field values with the RGB values of their decorator
     * Has no sense for fields other than lists
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelectWithDecorator()
    {
        return $this->getBind()->getQuerySelectWithDecorator();
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom()
    {
        return $this->getBind()->getQueryFrom();
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFromWithDecorator()
    {
        return $this->getBind()->getQueryFromWithDecorator();
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby()
    {
        return $this->getBind()->getQueryOrderby();
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby()
    {
        return $this->getBind()->getQueryGroupby();
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
    public function getQuerySelectAggregate($functions)
    {
        return $this->getBind()->getQuerySelectAggregate($functions);
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions()
    {
        return array('COUNT', 'COUNT_GRBY');
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao
     */
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_List_ValueDao();
    }

    /**
     * Display the field as a Changeset value.
     * Used in report table
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
        static $cache = [];

        if (isset($cache[$this->getId()][$changeset_id])) {
            return $cache[$this->getId()][$changeset_id];
        }

        //We have to fetch all values of the changeset as we are a list of value
        //This is the case only if we are multiple but an old changeset may
        //contain multiple values
        $values = array();
        foreach ($this->getBind()->getChangesetValues($changeset_id) as $v) {
            $val = $this->getBind()->formatChangesetValue($v);
            if ($val != '') {
                $values[] = $val;
            }
        }
        $changeset_value = implode(', ', $values);
        $cache[$this->getId()][$changeset_id] = $changeset_value;
        return $changeset_value;
    }

    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        $values = array();
        foreach ($this->getBind()->getChangesetValues($changeset_id) as $v) {
            $values[] = $this->getBind()->formatChangesetValueForCSV($v);
        }
        return implode(',', $values);
    }

    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_Report_Criteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria)
    {
        if (empty($this->criteria_value) || empty($this->criteria_value[$criteria->getReport()->getId()])) {
            $this->criteria_value = array();

            if (empty($this->criteria_value[$criteria->getReport()->getId()])) {
                $this->criteria_value[$criteria->getReport()->getId()] = array();

                if ($criteria->id > 0) {
                    foreach ($this->getCriteriaDao()->searchByCriteriaId($criteria->id) as $row) {
                        $this->criteria_value[$criteria->getReport()->getId()][] = $row['value'];
                    }
                }
            }
        } elseif (in_array('', $this->criteria_value[$criteria->getReport()->getId()])) {
            return '';
        }

        return $this->criteria_value[$criteria->getReport()->getId()];
    }

    /**
     * @throws Tracker_Report_InvalidRESTCriterionException
     */
    public function setCriteriaValueFromREST(Tracker_Report_Criteria $criteria, array $rest_criteria_value)
    {
        $searched_field_values = $rest_criteria_value[Tracker_Report_REST::VALUE_PROPERTY_NAME];
        $operator              = $rest_criteria_value[Tracker_Report_REST::OPERATOR_PROPERTY_NAME];

        if ($operator !== Tracker_Report_REST::OPERATOR_CONTAINS) {
            throw new Tracker_Report_InvalidRESTCriterionException("Unallowed operator for criterion field '$this->name' ($this->id). Allowed operators: [" . Tracker_Report_REST::OPERATOR_CONTAINS . "]");
        }

        if (is_numeric($searched_field_values)) {
            $values_to_match = array((int) $searched_field_values);
        } elseif (is_array($searched_field_values)) {
            $values_to_match = $searched_field_values;
        } else {
            throw new Tracker_Report_InvalidRESTCriterionException("Invalid format for criterion field '$this->name' ($this->id)");
        }

        $available_field_values = $this->getAllValues();
        $criterias              = array();

        foreach ($values_to_match as $value_to_match) {
            if (! is_numeric($value_to_match)) {
                throw new Tracker_Report_InvalidRESTCriterionException("Invalid format for criterion field '$this->name' ($this->id)");
            }

            if ($value_to_match == self::NONE_VALUE) {
                continue;
            }

            if (! isset($available_field_values[$value_to_match])) {
                continue;
            }

            $criterias[] = $this->formatCriteriaValue($value_to_match);
        }

        $this->setCriteriaValue($criterias, $criteria->getReport()->getId());

        return count($criterias) > 0;
    }

    public function exportCriteriaValueToXML(Tracker_Report_Criteria $criteria, SimpleXMLElement $xml_criteria)
    {
        if (! $this->getBind() instanceof Tracker_FormElement_Field_List_Bind_Static) {
            return;
        }

        $criteria_value = $this->getCriteriaValue($criteria);
        if (is_array($criteria_value) && count($criteria_value) > 0) {
            $criteria_value_node = $xml_criteria->addChild('criteria_value');
            $criteria_value_node->addAttribute('type', 'list');

            foreach ($criteria_value as $value_id) {
                if ($value_id == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    $criteria_value_node->addChild('none_value');
                } else {
                    $selected_value_node = $criteria_value_node->addChild('selected_value');
                    $selected_value_node->addAttribute('REF', 'V' . $value_id);
                }
            }
        }
    }

    public function setCriteriaValueFromXML(
        Tracker_Report_Criteria $criteria,
        SimpleXMLElement $xml_criteria_value,
        array $xml_field_mapping
    ) {
        if (! $this->getBind() instanceof Tracker_FormElement_Field_List_Bind_Static) {
            return;
        }

        if ((string) $xml_criteria_value['type'] !== 'list') {
            return;
        }

        $criteria_list_value = [];
        foreach ($xml_criteria_value->selected_value as $xml_selected_value) {
            $ref_value = (string) $xml_selected_value['REF'];

            if (! isset($xml_field_mapping[$ref_value])) {
                continue;
            }

            $field_value = $xml_field_mapping[$ref_value];
            assert($field_value instanceof Tracker_FormElement_Field_List_Bind_StaticValue);

            $criteria_list_value[] = $field_value;
        }

        if (isset($xml_criteria_value->none_value)) {
            $criteria_list_value[] = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
        }

        if (count($criteria_list_value) > 0) {
            $cache = XMLCriteriaValueCache::instance(spl_object_id($this));
            $cache->set($criteria->getReport()->getId(), $criteria_list_value);
        }
    }

    public function saveCriteriaValueFromXML(Tracker_Report_Criteria $criteria)
    {
        if (! $this->getBind() instanceof Tracker_FormElement_Field_List_Bind_Static) {
            return;
        }

        $report_id = $criteria->getReport()->getId();

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this));

        if (! $cache->has($report_id)) {
            return;
        }

        $value_in_field_value = $cache->get($criteria->getReport()->getId());
        $formatted_criteria_value = [];
        foreach ($value_in_field_value as $field_value) {
            assert($field_value instanceof Tracker_FormElement_Field_List_Bind_StaticValue);
            $formatted_criteria_value[] = (int) $field_value->getId();
        }

        $this->updateCriteriaValue($criteria, $formatted_criteria_value);
    }

    protected function formatCriteriaValue($value_to_match)
    {
        return $value_to_match;
    }

    /**
     * Format the criteria value submitted by the user for storage purpose (dao or session)
     *
     * @param mixed $value The criteria value submitted by the user
     *
     * @return mixed
     */
    public function getFormattedCriteriaValue($value)
    {
        if (empty($value['values'])) {
            $value['values'] = array('');
        }
        return $value['values'];
    }

    /**
     * Display the field value as a criteria
     * @param Tracker_Report_Criteria $criteria
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        if (! is_array($criteria_value)) {
            $criteria_value = array($criteria_value);
        }

        $multiple = ' ';
        $size     = ' ';
        $prefix_name = "criteria[$this->id][values]";
        $name        = $prefix_name . '[]';

        if ($criteria->is_advanced) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="' . min(7, count($this->getBind()->getAllValues()) + 2) . '" ';
        }

        $html .= '<input type="hidden" name="' . $prefix_name . '" />';
        $html .= '<select id="tracker_report_criteria_' . ($criteria->is_advanced ? 'adv_' : '') . $this->id . '"
                          name="' . $name . '" ' .
                          $size .
                          $multiple . '>';
        //Any value
        $selected = count($criteria_value) && !in_array('', $criteria_value) ? '' : 'selected="selected"';
        $html .= '<option value="" ' . $selected . ' title="' . $GLOBALS['Language']->getText('global', 'any') . '">' . $GLOBALS['Language']->getText('global', 'any') . '</option>';
        //None value
        $selected = in_array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID, $criteria_value) ? 'selected="selected"' : '';
        $html .= '<option value="' . Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID . '" ' . $selected . ' title="' . $GLOBALS['Language']->getText('global', 'none') . '">' . $GLOBALS['Language']->getText('global', 'none') . '</option>';
        //Field values
        foreach ($this->getBind()->getAllValues() as $id => $value) {
            $selected = in_array($id, $criteria_value) ? 'selected="selected"' : '';

            $styles = $this->getBind()->getSelectOptionStyles($id);

            $html .= '<option value="' . $id . '"  title="' . $this->getBind()->formatCriteriaValue($id) . '" ' . $selected . ' style="' . $styles['inline-styles'] . '" class="' . $styles['classes'] . '">';
            $html .= $this->getBind()->formatCriteriaValue($id);
            $html .= '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Add some additionnal information beside the criteria.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchCriteriaAdditionnalInfo($criteria)
    {
        return ''; //$this->getBind()->fetchDecoratorsAsJavascript();
    }
    /**
     * Add some additionnal information beside the field in the artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchArtifactAdditionnalInfo(?Tracker_Artifact_ChangesetValue $value, array $submitted_values)
    {
        return '';
    }

     /**
     * Add some additionnal information beside the field in the submit new artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchSubmitAdditionnalInfo(array $submitted_values)
    {
        return '';
    }

    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced()
    {
        return true;
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        return $this->getBind()->fetchRawValue($value);
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        return $this->getBind()->fetchRawValueFromChangeset($changeset);
    }

    /**
     * @return Tracker_FormElement_Field_Value_ListDao
     */
    protected function getValueDao()
    {
        return new Tracker_FormElement_Field_Value_ListDao();
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $selected_values = isset($submitted_values[$this->id]) ? $submitted_values[$this->id] : array();
        $default_values  = $this->getSubmitDefaultValues();

        return $this->_fetchField(
            'tracker_field_' . $this->id,
            'artifact[' . $this->id . ']',
            $default_values,
            $selected_values
        );
    }

    private function getSubmitDefaultValues()
    {
        if ($this->fieldHasEnableWorkflow()) {
            return array();
        }

        return $this->getBind()->getDefaultValues();
    }

     /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        return $this->_fetchFieldMasschange('tracker_field_' . $this->id, 'artifact[' . $this->id . ']', $this->getBind()->getDefaultValues());
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
    protected function fetchArtifactValue(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        $values = $submitted_values[$this->id] ?? [];
        $selected_values  = $value ? $value->getListValues() : array();
        return $this->_fetchField(
            'tracker_field_' . $this->id,
            'artifact[' . $this->id . ']',
            $selected_values,
            $values
        );
    }

     /**
     * Fetch the field value in artifact to be displayed in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param bool $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           mail format
     *
     * @return string
     */

    public function fetchMailArtifactValue(
        Tracker_Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text'
    ) {
        $output = '';
        switch ($format) {
            case 'html':
                if (empty($value) || !$value->getListValues()) {
                    return '-';
                }
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $tablo = array();
                $selected_values = !empty($value) ? $value->getListValues() : array();
                foreach ($selected_values as $value) {
                    $tablo[] = $this->getBind()->formatMailArtifactValue($value->getId());
                }
                $output = implode(', ', $tablo);
                break;
        }
        return $output;
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        $selected_values = $value ? $value->getListValues() : array();
        $tablo = array();

        if (empty($selected_values)) {
            return $this->getNoValueLabel();
        }

        if (count($selected_values) === 1 && isset($selected_values[Tracker_FormElement_Field_List_Bind::NONE_VALUE])) {
            return $this->getNoValueLabel();
        }

        foreach ($selected_values as $id => $selected) {
            $tablo[] = $this->getBind()->formatArtifactValue($id);
        }
        $html .= implode(', ', $tablo);
        return $html;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Indicate if a workflow is defined and enabled on a field_id.
     * @param $id the field_id
     * @return bool , true if a workflow is defined and enabled on the field_id
     */
    public function fieldHasEnableWorkflow()
    {
        $workflow = $this->getWorkflow();
        if (!empty($workflow) && $workflow->is_used) {
            return $workflow->field_id === $this->id;
        }
        return false;
    }

     /**
     * Indicate if a workflow is defined on a field_id.
     * @param $id the field_id
     * @return bool , true if a workflow is defined on the field_id
     */
    public function fieldHasDefineWorkflow()
    {
        $workflow = $this->getWorkflow();
        if (!empty($workflow)) {
            return $workflow->field_id === $this->id;
        }
        return false;
    }

    /**
     * Get the workflow of the tracker.
     * @return Workflow Object
     */
    public function getWorkflow()
    {
        return $this->getTracker()->getWorkflow();
    }

    /**
     * Validate a value
     * @param mixed $value data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        $valid          = true;
        $field_value_to = null;

        if ($this->fieldHasEnableWorkflow()) {
            $last_changeset = $artifact->getLastChangeset();

            try {
                $field_value_to = $this->getBind()->getValue($value);
                if (!$last_changeset) {
                    if (!$this->isTransitionValid(null, $field_value_to)) {
                           $this->has_errors = true;
                           $valid = false;
                    }
                } else {
                    if ($last_changeset->getValue($this) != null) {
                        foreach ($last_changeset->getValue($this)->getListValues() as $id => $value) {
                            if ($value != $field_value_to) {
                                if (!$this->isTransitionValid($value, $field_value_to)) {
                                    $this->has_errors = true;
                                    $valid = false;
                                }
                            }
                        }
                    } else {
                        if (!$this->isTransitionValid(null, $field_value_to)) {
                            $this->has_errors = true;
                            $valid = false;
                        }
                    }
                }
            } catch (Tracker_FormElement_InvalidFieldValueException $exexption) {
                $valid = false;
            }

            if ($valid) {
                $valid = $this->getTransitionListValidator()->checkTransition(
                    $this,
                    $value,
                    $last_changeset
                );
            }
        }

        if ($valid) {
            return true;
        } else {
            if ($field_value_to !== null && ! is_array($field_value_to)) {
                if (is_array($field_value_to)) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'The transition is not valid.'));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'transition_not_valid', array($field_value_to->getLabel())));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'transition_to_none'));
            }
            return false;
        }
    }


    protected function isTransitionValid($field_value_from, $field_value_to)
    {
        if (!$this->fieldHasEnableWorkflow()) {
            return true;
        } else {
            $workflow = $this->getWorkflow();
            if ($workflow->isTransitionExist($field_value_from, $field_value_to)) {
                return true;
            } else {
                return false;
            }
        }
    }

    protected function getSelectedValue($selected_values)
    {
        if ($this->getBind()) {
            foreach ($this->getBind()->getBindValues() as $id => $value) {
                if (isset($selected_values[$id])) {
                    $from = $value;
                    return $from;
                }
            }
            return null;
        }
    }

    /**
     * protected for testing purpose
     */
    protected function getTransitionListValidator(): TransitionListValidator
    {
        return new TransitionListValidator(TransitionFactory::instance());
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getAllValues()
    {
        return $this->getBind()->getAllValues();
    }

    /**
     * @return array
     */
    public function getAllVisibleValues()
    {
        return $this->getBind()->getAllVisibleValues();
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[] array of BindValues that are not hidden + none value if any
     */
    public function getVisibleValuesPlusNoneIfAny()
    {
        $values = $this->getAllVisibleValues();

        if ($values) {
            if (! $this->isRequired()) {
                $none = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
                $values = array($none->getId() => $none) + $values;
            }
        }

        return $values;
    }

    /**
     * @return Tracker_FormElement_Field_List_Value|null null if not found
     */
    public function getListValueById($value_id)
    {
        foreach ($this->getVisibleValuesPlusNoneIfAny() as $value) {
            if ($value->getId() == $value_id) {
                return $value;
            }
        }
    }

    /**
     *
     * @return string
     */
    public function getFirstValueFor(Tracker_Artifact_Changeset $changeset)
    {
        if ($this->userCanRead()) {
            $value = $changeset->getValue($this);
            if ($value && ($last_values = $value->getListValues())) {
                // let's assume there is no more that one status
                if ($label = array_shift($last_values)->getLabel()) {
                    return $label;
                }
            }
        }
    }

    /**
     * @param array  $selected_values
     * @param mixed  $submitted_values_for_this_list
     *
     * @return string
     */
    protected function _fetchField(string $id, string $name, $selected_values, $submitted_values_for_this_list = array())
    {
        $html     = '';
        $purifier = Codendi_HTMLPurifier::instance();

        if ($name) {
            if ($this->isMultiple()) {
                $name .= '[]';
            }
            $name = 'name="' . $purifier->purify($name) . '"';
        }

        if ($id) {
            $id = 'id="' . $id . '"';
        }

        $html .= $this->fetchFieldContainerStart($id, $name);

        $from = $this->getSelectedValue($selected_values);
        if ($from == null && !isset($submitted_values_for_this_list)) {
            $none_is_selected = isset($selected_values[Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID]);
        } else {
            $none_is_selected = ($submitted_values_for_this_list == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }

        if (!$this->fieldHasEnableWorkflow()) {
            $none_value = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
            $html .= $this->fetchFieldValue($none_value, $name, $none_is_selected);
        }

        if (($submitted_values_for_this_list) && !is_array($submitted_values_for_this_list)) {
            $submitted_values_array[]       = $submitted_values_for_this_list;
            $submitted_values_for_this_list = $submitted_values_array;
        }

        foreach ($this->getBind()->getAllValues() as $id => $value) {
            $transition_id = null;
            if ($this->isTransitionValid($from, $value)) {
                $transition_id = $this->getTransitionId($from, $value->getId());
                if (!empty($submitted_values_for_this_list)) {
                    $is_selected = in_array($id, array_values($submitted_values_for_this_list));
                } else {
                    $is_selected = isset($selected_values[$id]);
                }
                if ($this->userCanMakeTransition($transition_id)) {
                    if (! $value->isHidden() || $value === $from) {
                        $html .= $this->fetchFieldValue($value, $name, $is_selected);
                    }
                }
            }
        }

        $html .= $this->fetchFieldContainerEnd();
        return $html;
    }

    protected function fetchFieldContainerStart($id, $name)
    {
        $html     = '';
        $multiple = '';
        $size     = '';
        $required = '';
        if ($this->isMultiple()) {
            $multiple = 'multiple="multiple"';
            $size     = 'size="' . min($this->getMaxSize(), count($this->getBind()->getBindValues()) + 2) . '"';
        }
        if ($this->isRequired()) {
            $required = 'required';
        }
        $html .= "<select $id $name $multiple $size $required>";
        return $html;
    }

    protected function fetchFieldValue(Tracker_FormElement_Field_List_Value $value, $name, $is_selected)
    {
        $id = $value->getId();
        if ($id == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
            $label = $value->getLabel();
        } else {
            $label = $this->getBind()->formatArtifactValue($id);
        }

        $styles = $this->getBind()->getSelectOptionStyles($id);

        $selected = $is_selected ? 'selected="selected"' : '';

        return '<option value="' . $id . '" ' . $selected . ' title="' . $label . '" style="' . $styles['inline-styles'] . '" class="' .  $styles['classes'] . ' ">' . $label . '</option>';
    }

    protected function fetchFieldContainerEnd()
    {
        return '</select>';
    }


    protected function _fetchFieldMasschange($id, $name, $selected_values)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $multiple = ' ';
        $size     = ' ';
        if ($this->isMultiple()) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="' . min($this->getMaxSize(), count($this->getBind()->getAllValues()) + 2) . '" ';
            if ($name) {
                $name .= '[]';
            }
        }
        $html .= '<select ';
        if ($id) {
            $html .= 'id="' . $id . '" ';
        }
        if ($name) {
            $html .= 'name="' . $name . '" ';
        }
        $html .= $size . $multiple . '>';

        $html .= '<option value="' . $purifier->purify(BindStaticValueUnchanged::VALUE_ID) . '" selected="selected">' .
            $GLOBALS['Language']->getText('global', 'unchanged') . '</option>';
        $html .= '<option value="' . Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID . '">' . $GLOBALS['Language']->getText('global', 'none') . '</option>';

        foreach ($this->getBind()->getAllValues() as $id => $value) {
            if (!$value->isHidden()) {
                $styles  = $this->getBind()->getSelectOptionStyles($id);

                $html .= '<option value="' . $id . '" title="' . $this->getBind()->formatArtifactValue($id) . '" style="' . $styles['inline-styles'] . '" classe="' . $styles['classes'] . '">';
                $html .= $this->getBind()->formatArtifactValue($id);
                $html .= '</option>';
            }
        }

        $html .= '</select>';
        return $html;
    }


    protected function getMaxSize()
    {
        return 7;
    }

    /**
     * Fetch the changes that has been made to this field in a followup
     * @param Tracker_Artifact $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     */
    public function fetchFollowUp($artifact, $from, $to)
    {
        $html = '';
        $values = array();
        $from_value = false;
        if ($from && isset($from['changeset_id'])) {
            foreach ($this->getBind()->getChangesetValues($from['changeset_id']) as $v) {
                if ($v['id'] != Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                    $values[] = $this->getBind()->formatChangesetValue($v);
                }
            }
            $from_value = implode(', ', $values);
        }

        if (!$from_value) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ';
        } else {
            $html .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'changed_from') . ' ' . $from_value . '  ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'to') . ' ';
        }

        $values = array();
        foreach ($this->getBind()->getChangesetValues($to['changeset_id']) as $v) {
            $values[] = $this->getBind()->formatChangesetValue($v);
        }
        $html .= implode(', ', $values);
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '';
        $html .= $this->_fetchField('', '', $this->getBind()->getDefaultValues());
        return $html;
    }

    /**
     * Fetch the html code to display the field value in tooltip
     * @param Tracker_Artifact_ChangesetValue_List $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html           = '';
        $last_changeset = $artifact->getLastChangeset();
        if ($value && $last_changeset !== null) {
            $html .= $this->fetchChangesetValue($artifact->id, $last_changeset->getId(), $value);
        }
        return $html;
    }

    /**
     * @see Tracker_FormElement_Field::fetchCardValue()
     */
    public function fetchCardValue(
        Tracker_Artifact $artifact,
        ?Tracker_CardDisplayPreferences $display_preferences = null
    ) {
        $html = '';
        //We have to fetch all values of the changeset as we are a list of value
        //This is the case only if we are multiple but an old changeset may
        //contain multiple values
        $values = array();
        foreach ($this->getBind()->getChangesetValues($artifact->getLastChangeset()->id) as $v) {
            $val = $this->getBind()->formatCardValue($v, $display_preferences);
            if ($val != '') {
                $values[] = $val;
            }
        }
        $html .= implode(' ', $values);

        return $html;
    }

    /**
     * Update the form element.
     * Override the parent function to handle binds
     */
    protected function processUpdate(Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $redirect = false)
    {
        $redirect = false;
        if ($request->exist('bind')) {
            $redirect = $this->getBind()->process($request->get('bind'), $no_redirect = true);
        }
        parent::processUpdate($layout, $request, $current_user, $redirect);
    }

    /**
     * Hook called after a creation of a field
     *
     * @param array $form_element_data
     * @param bool $tracker_is_empty
     */
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
        parent::afterCreate($form_element_data, $tracker_is_empty);
        $type      = isset($form_element_data['bind-type']) ? $form_element_data['bind-type'] : '';
        $bind_data = isset($form_element_data['bind']) ? $form_element_data['bind'] : array();

        $bf = new Tracker_FormElement_Field_List_BindFactory();
        if ($this->bind = $bf->createBind($this, $type, $bind_data)) {
            $dao = new Tracker_FormElement_Field_ListDao();
            $dao->save($this->getId(), $bf->getType($this->bind));
        }
    }

    /**
     * Transforms FormElement_List into a SimpleXMLElement
     */
    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter
    ) {
        parent::exportToXML($root, $xmlMapping, $project_export_context, $user_xml_exporter);
        if ($this->getBind() && $this->shouldBeBindXML()) {
            $child = $root->addChild('bind');
            $bf = new Tracker_FormElement_Field_List_BindFactory();
            $child->addAttribute('type', $bf->getType($this->getBind()));
            $this->getBind()->exportToXML($child, $xmlMapping, $project_export_context, $user_xml_exporter);
        }
    }

    /**
     * Say if we export the bind in the XML
     *
     * @return bool
     */
    public function shouldBeBindXML()
    {
        return true;
    }

    /**
     * Continue the initialisation from an xml (FormElementFactory is not smart enough to do all stuff.
     * Polymorphism rulez!!!
     *
     * @param SimpleXMLElement                          $xml         containing the structure of the imported Tracker_FormElement
     * @param array                                     &$xmlMapping where the newly created formElements indexed by their XML IDs are stored (and values)
     */
    public function continueGetInstanceFromXML(
        $xml,
        &$xmlMapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        TrackerXmlImportFeedbackCollector $feedback_collector
    ) {
        parent::continueGetInstanceFromXML($xml, $xmlMapping, $user_finder, $feedback_collector);
        // if field is a list add bind
        if ($xml->bind) {
            $bind = $this->getBindFactory()->getInstanceFromXML($xml->bind, $this, $xmlMapping, $user_finder);
            $this->setBind($bind);
        }
    }

    /**
     * Callback called after factory::saveObject. Use this to do post-save actions
     *
     * @param Tracker $tracker The tracker
     * @param bool $tracker_is_empty
     */
    public function afterSaveObject(Tracker $tracker, $tracker_is_empty, $force_absolute_ranking)
    {
        $bind = $this->getBind();
        $this->getListDao()->save($this->getId(), $this->getBindFactory()->getType($bind));
        $bind->saveObject();
    }

    /**
     * Get an instance of Tracker_FormElement_Field_ListDao
     *
     * @return Tracker_FormElement_Field_ListDao
     */
    public function getListDao()
    {
        return new Tracker_FormElement_Field_ListDao();
    }

    /**
     * Get an instance of Tracker_FormElement_Field_List_BindFactory
     *
     * @return Tracker_FormElement_Field_List_BindFactory
     */
    public function getBindFactory()
    {
        return new Tracker_FormElement_Field_List_BindFactory();
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ) {
        return $this->getValueDao()->create($changeset_value_id, $value);
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $changeset_value = null;
        $value_ids = $this->getValueDao()->searchById($value_id, $this->id);
        $bindvalue_ids = array();
        if ($value_ids) {
            foreach ($value_ids as $v) {
                $bindvalue_ids[] = $v['bindvalue_id'];
            }
        }
        $bind_values = array();
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValues($bindvalue_ids);
        }
        $changeset_value = new Tracker_Artifact_ChangesetValue_List($value_id, $changeset, $this, $has_changed, $bind_values);
        return $changeset_value;
    }

    public function getRESTBindingProperties()
    {
        $bind = $this->getBind();
        return $bind->getRESTBindingProperties();
    }

    public function getFieldDataFromRESTValue(array $value, ?Tracker_Artifact $artifact = null)
    {
        if (array_key_exists('bind_value_ids', $value) && is_array($value['bind_value_ids'])) {
            return array_map('intval', $value['bind_value_ids']);
        }
        throw new Tracker_FormElement_InvalidFieldValueException('List fields values must be passed as an array of ids (integer) in \'bind_value_ids\''
           . ' Example: {"field_id": 1548, "bind_value_ids": [457]}');
    }

    public function getFieldDataFromRESTValueByField(array $value, ?Tracker_Artifact $artifact = null)
    {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string the rest field value
     *
     * @return mixed the field data corresponding to the rest_value for artifact submision
     */
    public function getFieldData($value)
    {
        if ($value === $GLOBALS['Language']->getText('global', 'none')) {
            return Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID;
        }

        $bind = $this->getBind();
        if ($bind != null) {
            $value = $bind->getFieldData($value, $this->isMultiple());
            if ($value != null) {
                return $value;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $previous_changesetvalue, $new_value)
    {
        if (!is_array($new_value)) {
            $new_value = array($new_value);
        }
        if (empty($new_value)) {
            $new_value = array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }
        if ($previous_changesetvalue) {
            $old_value = $previous_changesetvalue->getValue();
        }
        if (empty($old_value)) {
            $old_value = array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID);
        }
        sort($old_value);
        sort($new_value);
        return $old_value != $new_value;
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported()
    {
        if ($b = $this->getBind()) {
            return $b->isNotificationsSupported();
        }
        return false;
    }

    protected function permission_is_authorized($type, $transition_id, $user_id, $group_id)
    {
        include_once __DIR__ . '/../../../../../src/www/project/admin/permissions.php';

        return permission_is_authorized($type, $transition_id, $user_id, $group_id);
    }

    /**
     * Check if the user can make the transition
     *
     * @param int  $transition_id The id of the transition
     * @param PFUser $user          The user. If null, take the current user
     *
     *@return bool true if user has permission on this field
     */
    public function userCanMakeTransition($transition_id, ?PFUser $user = null)
    {
        if ($transition_id) {
            $group_id = $this->getTracker()->getGroupId();

            if (!$user) {
                $user = $this->getCurrentUser();
            }
            return $this->permission_is_authorized('PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition_id, $user->getId(), $group_id);
        }
        return true;
    }

    /**
     * Get a recipients list for notifications. This is filled by users fields for example.
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset
     *
     * @return string[]
     */
    public function getRecipients(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        return $this->getBind()->getRecipients($changeset_value);
    }

    protected function getTransitionId($from, $to)
    {
        return TransitionFactory::instance()->getTransitionId($this->getTracker(), $from, $to);
    }

    public function getDefaultValue()
    {
        $default_array = $this->getBind()->getDefaultValues();
        if (! $default_array) {
            return array(Tracker_FormElement_Field_List_Bind::NONE_VALUE);
        }
        return array_keys($default_array);
    }


    public function getDefaultRESTValue()
    {
        return $this->getBind()->getDefaultRESTValues();
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value)
    {
        $this->has_errors = ! ($this->isPossibleValue($value) && $this->validate($artifact, $value));

        return ! $this->has_errors;
    }

    /**
     * @return bool
     */
    protected function isPossibleValue($value)
    {
        $is_possible_value   = true;

        if (is_array($value)) {
            foreach ($value as $id) {
                $is_possible_value = $is_possible_value && $this->checkValueExists($id);
            }
        } else {
            $is_possible_value = $this->checkValueExists($value);
        }

        return $is_possible_value;
    }

    /**
     * @return bool
     */
    public function checkValueExists($value_id)
    {
        return $this->getBind()->isExistingValue($value_id) ||
               $value_id == Tracker_FormElement_Field_List::NONE_VALUE ||
               $value_id == Tracker_FormElement_Field_List::NOT_INDICATED_VALUE;
    }

    /**
     * Validate a required field
     *
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $value      The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Tracker_Artifact $artifact, $value)
    {
        $this->has_errors = false;

        if ($this->isEmpty($value, $artifact) && $this->isRequired()) {
            $this->addRequiredError();
        }

        return ! $this->has_errors;
    }

    public function isEmpty($value, Tracker_Artifact $artifact)
    {
        return $this->isNone($value);
    }

    /**
     * @see Tracker_FormElement_Field_Shareable
     */
    public function fixOriginalValueIds(array $value_mapping)
    {
        $this->getBind()->fixOriginalValueIds($value_mapping);
    }

    /**
     * @see Tracker_FormElement::process()
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        parent::process($layout, $request, $current_user);
        if ($request->get('func') == 'get-values') {
            $GLOBALS['Response']->sendJSON($this->getBind()->fetchFormattedForJson());
        }
    }

    public function fetchFormattedForJson()
    {
        $json = parent::fetchFormattedForJson();
        $json['values'] = $this->getBind()->fetchFormattedForJson();
        return $json;
    }

    public function getRESTAvailableValues()
    {
        $values = null;
        $bind = $this->getBind();
        if ($bind != null) {
            $values = $bind->getRESTAvailableValues();
        }
        return $values;
    }

    /**
     * @param string $new_value
     *
     * @return int | null
     */
    public function addBindValue($new_value)
    {
        return $this->getBind()->addValue($new_value);
    }

    /**
     * Get the html to select a default value
     *
     * @return string html
     */
    public function getSelectDefaultValues($default_values)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';

        //Select default values
        $html .= '<p>';
        $html .= '<strong>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'select_default_value') . '</strong><br />';
        $html .= '<select name="bind[default][]" class="bind_default_values" size="7" multiple="multiple">';
        foreach ($this->getAllVisibleValues() as $v) {
            $selected = isset($default_values[$v->getId()]) ? 'selected="selected"' : '';
            $html .= '<option value="' . $v->getId() . '" ' . $selected . '>' . $hp->purify($v->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . '</option>';
        }
        $html .= '</select>';
        $html .= '</p>';

        return $html;
    }
}
