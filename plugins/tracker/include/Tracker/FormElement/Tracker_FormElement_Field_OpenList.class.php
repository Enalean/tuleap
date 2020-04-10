<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001 - 2009. All rights reserved
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueUnchanged;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;

class Tracker_FormElement_Field_OpenList extends Tracker_FormElement_Field_List implements BindVisitor
{
    public const BIND_PREFIX = 'b';
    public const OPEN_PREFIX = 'o';
    public const NEW_VALUE_PREFIX = '!';

    public $default_properties = array(
        'hint' => array(
            'value' => 'Type in a search term',
            'type'  => 'string',
            'size'  => 40,
        ),
    );

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return true;
    }

    /**
     * fetch the html widget
     *
     * @param array $values The existing values. default is empty
     * @param mixed $name   A string for a given name or true for the default name (artifact[123]), false for no name.
     *
     * @return string html
     */
    public function fetchOpenList($values = array(), $name = true)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($name === true) { //we want the default name
            $name = 'name="artifact[' . $this->id . ']"';
        } elseif ($name === false) { //we don't want a name
            $name = '';
        } else { //we keep the given name
            $name = 'name="' . $hp->purify($name) . '"';
        }
        $html .= '<div class="textboxlist">
                    <input id="tracker_field_' . $this->id . '"
                           ' . $name . '
                           style="width:98%"
                           type="text"></div>';

        $html .= '<div class="textboxlist-auto" id="tracker_artifact_textboxlist_' . $this->id . '">';
        $html .= '<div class="default">' .  $hp->purify($this->getProperty('hint'), CODENDI_PURIFIER_LIGHT) . '</div>';
        $html .= '<ul class="feed">';
        //Field values
        foreach ($values as $v) {
            if ($v->getId() != 100) {
                $html .= '<li value="' . $v->getJsonId() . '">';
                $html .= $hp->purify($v->getLabel());
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

     /**
     * fetch the html widget
     *
     * @param array $values The existing values. default is empty
     * @param mixed $name   A string for a given name or true for the default name (artifact[123]), false for no name.
     *
     * @return string html
     */
    public function fetchOpenListMasschange($values = array(), $name = true)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($name === true) { //we want the default name
            $name = 'name="artifact[' . $this->id . ']"';
        } elseif ($name === false) { //we don't want a name
            $name = '';
        } else { //we keep the given name
            $name = 'name="' . $hp->purify($name) . '"';
        }
        $html .= '<div class="textboxlist">
                    <input id="tracker_field_' . $this->id . '"
                           ' . $name . '
                           style="width:98%"
                           type="text"></div>';

        $html .= '<div class="textboxlist-auto" id="tracker_artifact_textboxlist_' . $this->id . '">';
        $html .= '<div class="default">' .  $hp->purify($this->getProperty('hint'), CODENDI_PURIFIER_LIGHT) . '</div>';
        $html .= '<ul class="feed">';
        $html .= '<li value="' . $hp->purify(BindStaticValueUnchanged::VALUE_ID) . '">';
        $html .= dgettext('tuleap-tracker', 'Unchanged');
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        if (isset($submitted_values[$this->id])) {
            return $this->fetchOpenList($this->toObj($submitted_values[$this->id]));
        }
        return $this->fetchOpenList($this->getDefaultValues());
    }

    private function getDefaultValues()
    {
        $default_values_ids = array_keys($this->getBind()->getDefaultValues());
        return $this->getBind()->getBindValuesForIds($default_values_ids);
    }

     /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        return $this->fetchOpenListMasschange();
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
        assert($value instanceof Tracker_Artifact_ChangesetValue_List);
        $selected_values = $value ? $value->getListValues() : array();
        if (is_array($submitted_values) && isset($submitted_values[$this->id])) {
            return $this->fetchOpenList($this->toObj($submitted_values[$this->id]));
        }
        return $this->fetchOpenList($selected_values);
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
        $labels = array();
        $selected_values = $value ? $value->getListValues() : array();

        if (empty($selected_values)) {
            return $this->getNoValueLabel();
        }

        $purifier = Codendi_HTMLPurifier::instance();
        foreach ($selected_values as $id => $v) {
            if ($id != 100) {
                $labels[] = $purifier->purify($v->getLabel());
            }
        }
        return implode(', ', $labels);
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param bool $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
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
        if (empty($value) || ! $value->getListValues()) {
            return '-';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $selected_values = !empty($value) ? $value->getListValues() : array();
                foreach ($selected_values as $value) {
                    if ($value->getId() != 100) {
                        $output .= $value->getLabel();
                    }
                }
                break;
        }
        return $output;
    }


    public function textboxlist($keyword, $limit = 10)
    {
        $json_values = array();
        $matching_values = $this->getBind()->getValuesByKeyword($keyword, $limit);
        $nb = count($matching_values);
        if ($nb < $limit) {
            foreach ($this->getOpenValueDao()->searchByKeyword($this->getId(), $keyword, $limit - $nb) as $row) {
                $matching_values[] = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['label']
                );
            }
        }
        foreach ($matching_values as $v) {
            $json_values[] = $v->fetchForOpenListJson();
        }
        return json_encode($json_values);
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $has_name  = false;
        return $this->fetchOpenList($this->getDefaultValues(), $has_name);
    }

    /**
     * Return the dao
     *
     * @return Tracker_FormElement_Field_OpenListDao The dao
     */
    protected function getDao()
    {
        return new Tracker_FormElement_Field_OpenListDao();
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'open_list');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'open_list_description');

        return 'Provide a textbox containing an list of values, with autocompletion';
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-scroll-pane-list.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-scroll-pane-list--plus.png');
    }

    protected function getValueDao()
    {
        return new Tracker_FormElement_Field_Value_OpenListDao();
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
        foreach ($value_ids as $v) {
            if ($v['bindvalue_id']) {
                $bindvalue_ids[] = $v['bindvalue_id'];
            }
        }
        $bind_values = array();
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValuesForIds($bindvalue_ids);
        }

        $list_values = array();
        foreach ($value_ids as $v) {
            if ($v['bindvalue_id']) {
                if (isset($bind_values[$v['bindvalue_id']])) {
                    $list_values[] = $bind_values[$v['bindvalue_id']];
                }
            } else {
                if ($v = $this->getOpenValueById($v['openvalue_id'])) {
                    $list_values[] = $v;
                }
            }
        }
        $changeset_value = new Tracker_Artifact_ChangesetValue_OpenList($value_id, $changeset, $this, $has_changed, $list_values);
        return $changeset_value;
    }

    protected function getOpenValueDao()
    {
        return new Tracker_FormElement_Field_List_OpenValueDao();
    }

    protected $cache_openvalues = array();

    public function getOpenValueById($oid)
    {
        if (! isset($this->cache_openvalues[$oid])) {
            $this->cache_openvalues[$oid] = null;
            if ($row = $this->getOpenValueDao()->searchById($this->getId(), $oid)->getRow()) {
                $this->cache_openvalues[$oid] = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['label']
                );
            }
        }
        return $this->cache_openvalues[$oid];
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ) {
        $openvalue_dao = $this->getOpenValueDao();
        // the separator is a comma
        $values = $this->sanitize($value);
        $value_ids = array();
        foreach ($values as $v) {
            $bindvalue_id = null;
            $openvalue_id = null;
            switch ($v[0]) {
                case self::BIND_PREFIX: // bind value
                    $bindvalue_id = (int) substr($v, 1);
                    break;
                case self::OPEN_PREFIX: // open value
                    $openvalue_id = (int) substr($v, 1);
                    break;
                case self::NEW_VALUE_PREFIX: // new open value
                    $openvalue_id = $openvalue_dao->create($this->getId(), substr($v, 1));
                    break;
                default:
                    break;
            }
            if ($bindvalue_id || $openvalue_id) {
                $value_ids[] = array(
                    'bindvalue_id' => $bindvalue_id,
                    'openvalue_id' => $openvalue_id,
                );
            }
        }

        if (empty($value_ids)) {
            return true;
        }

        return $this->getValueDao()->create($changeset_value_id, $value_ids);
    }

    /**
     * Remove bad stuff
     *
     * @param string $submitted_value
     *
     * @return array
     */
    protected function sanitize($submitted_value)
    {
        $values = explode(',', (string) $submitted_value);
        $sanitized = array();
        foreach ($values as $v) {
            $v = trim($v);
            if ($v) {
                switch ($v[0]) {
                    case self::BIND_PREFIX: // bind value
                        if ($bindvalue_id = (int) substr($v, 1)) {
                            $sanitized[] = $v;
                        }
                        break;
                    case self::OPEN_PREFIX: // open value
                        if ($openvalue_id = (int) substr($v, 1)) {
                            $sanitized[] = $v;
                        }
                        break;
                    case self::NEW_VALUE_PREFIX:
                        $sanitized[] = $v;
                        break;
                    default:
                        break;
                }
            }
        }
        return $sanitized;
    }

    protected function toObj($submitted_value)
    {
        $values    = explode(',', (string) $submitted_value);
        $sanitized = array();
        foreach ($values as $v) {
            $v = trim($v);
            if ($v) {
                switch ($v[0]) {
                    case self::BIND_PREFIX: // bind value
                        if ($bindvalue_id = (int) substr($v, 1)) {
                            $sanitized[] = $this->getBind()->getBindValueById($bindvalue_id);
                        }
                        break;
                    case self::OPEN_PREFIX: // open value
                        if ($openvalue_id = (int) substr($v, 1)) {
                            $v = $this->getOpenValueById($openvalue_id);
                            $sanitized[] = $v;
                        }
                        break;
                    case self::NEW_VALUE_PREFIX: // new open value
                        $sanitized[] = new Tracker_FormElement_Field_List_UnsavedValue(substr($v, 1));
                        break;
                    default:
                        break;
                }
            }
        }

        return $sanitized;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return $old_value->getValue() != $this->sanitize($new_value);
    }

    /**
     * Display the field as a Changeset value.
     * Used in report table
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
        $arr = array();
        $bindtable = $this->getBind()->getBindtableSqlFragment();
        $values = $this->getDao()->searchChangesetValues(
            $changeset_id,
            $this->id,
            $bindtable['select'],
            $bindtable['select_nb'],
            $bindtable['from'],
            $bindtable['join_on_id']
        );
        foreach ($values as $row) {
            if ($row['openvalue_label']) {
                $v = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['openvalue_label']
                );
            } else {
                $v = $this->getBind()->getValueFromRow($row);
            }
            if ($v) {
                $arr[] = $v->fetchFormatted();
            }
        }
        $html = implode(', ', $arr);
        return $html;
    }

    /**
     * @see Tracker_FormElement_Field::fetchCardValue()
     */
    public function fetchCardValue(Tracker_Artifact $artifact, ?Tracker_CardDisplayPreferences $display_preferences = null)
    {
        $value = $artifact->getLastChangeset()->getValue($this);
        return $this->fetchTooltipValue($artifact, $value);
    }

    /**
     * Display the field for CSV data export
     * Used in CSV data export
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        $arr = array();
        $bindtable = $this->getBind()->getBindtableSqlFragment();
        $values = $this->getDao()->searchChangesetValues(
            $changeset_id,
            $this->id,
            $bindtable['select'],
            $bindtable['select_nb'],
            $bindtable['from'],
            $bindtable['join_on_id']
        );
        foreach ($values as $row) {
            if ($row['openvalue_label']) {
                $v = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['openvalue_label']
                );
            } else {
                $v = $this->getBind()->getValueFromRow($row);
            }
            if ($v) {
                $arr[] = $v->fetchFormattedForCSV();
            }
        }
        $txt = implode(',', $arr);
        return $txt;
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao
     */
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_OpenList_ValueDao();
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
            $criteria_value = $this->extractCriteriaValue($this->getCriteriaValue($criteria));
            $openvalues     = array();
            $bindvalues     = array();
            foreach ($criteria_value as $v) {
                if (is_a($v, 'Tracker_FormElement_Field_List_UnsavedValue')) {
                    //ignore it
                } elseif (is_a($v, 'Tracker_FormElement_Field_List_OpenValue')) {
                    $openvalues[] = $v->getId();
                } else { //bindvalue
                    $bindvalues[] = $v->getId();
                }
            }
            //Only filter query if criteria is valuated
            if ($openvalues || $bindvalues) {
                $a = 'A_' . $this->id;
                $b = 'B_' . $this->id;
                $statement = '';
                if ($openvalues) {
                    $statement .= "$b.openvalue_id IN (" . $this->getCriteriaDao()->getDa()->escapeIntImplode($openvalues) . ")";
                }
                if ($bindvalues) {
                    if ($statement) {
                        $statement .= ' OR ';
                    }
                    $statement .= "$b.bindvalue_id IN (" . $this->getCriteriaDao()->getDa()->escapeIntImplode($bindvalues) . ")";
                }
                return " INNER JOIN tracker_changeset_value AS $a
                         ON ($a.changeset_id = c.id
                             AND $a.field_id = " . $this->id . "
                         )
                         INNER JOIN tracker_changeset_value_openlist AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND ($statement)
                         )
                         ";
            }
        }
        return '';
    }

    protected function formatCriteriaValue($value_to_match)
    {
        return 'b' . $value_to_match;
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom()
    {
        return $this->getBind()->getQueryFrom('tracker_changeset_value_openlist');
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFromWithDecorator()
    {
        return $this->getBind()->getQueryFromWithDecorator('tracker_changeset_value_openlist');
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
        return ''; //$this->getBind()->getCriteriaWhere($this->getCriteriaValue($criteria));
    }

    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_Report_Criteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria)
    {
        if (! isset($this->criteria_value)) {
            $this->criteria_value = array();
        }

        if (! isset($this->criteria_value[$criteria->report->id])) {
            $this->criteria_value[$criteria->report->id] = '';
            $dao = $this->getCriteriaDao();
            if ($dao && $row = $dao->searchByCriteriaId($criteria->id)->getRow()) {
                $this->criteria_value[$criteria->report->id] = $row['value'];
            }
        }
        return $this->criteria_value[$criteria->report->id];
    }

    public function exportCriteriaValueToXML(Tracker_Report_Criteria $criteria, SimpleXMLElement $xml_criteria)
    {
        return;
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
        return $value;
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
        $criteria_value = $this->extractCriteriaValue($this->getCriteriaValue($criteria));

        $name = "criteria[$this->id]";
        return $this->fetchOpenList($criteria_value, $name);
    }


    protected function extractCriteriaValue($criteria_value)
    {
        //switch to array
        if (! is_array($criteria_value)) {
            $criteria_value = explode(',', $criteria_value);
        }

        //first extract open and unsaved values
        $bindvalue_ids = array();
        foreach ($criteria_value as $key => $val) {
            $val = trim($val);
            if (!$val) {
                unset($criteria_value[$key]);
            } elseif ($val[0] === self::OPEN_PREFIX) {
                if ($v = $this->getOpenValueById(substr($val, 1))) {
                    $criteria_value[$key] = $v;
                } else {
                    unset($criteria_value[$key]);
                }
            } elseif ($val[0] === self::BIND_PREFIX) {
                $bindvalue_ids[] = substr($val, 1);
                $criteria_value[$key] = $val; //store the trimmed val
            } elseif ($val[0] === self::NEW_VALUE_PREFIX) {
                $criteria_value[$key] = new Tracker_FormElement_Field_List_UnsavedValue(substr($val, 1));
            } else {
                unset($criteria_value[$key]);
            }
        }

        //load bind values
        $bind_values = array();
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValues($bindvalue_ids);
        }
        //then extract bindvalues from the criteria list
        foreach ($criteria_value as $key => $val) {
            if (is_string($val)) {
                $val = substr($val, 1);
                if (! empty($bind_values[$val])) {
                    $criteria_value[$key] = $bind_values[$val];
                } else {
                    unset($criteria_value[$key]);
                }
            }
        }
        return $criteria_value;
    }

    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced()
    {
        return false;
    }

    public function getFieldDataFromRESTValue(array $value, ?Tracker_Artifact $artifact = null)
    {
        if (isset($value['value']) && array_key_exists('bind_value_objects', $value['value']) && is_array($value['value']['bind_value_objects'])) {
            return $this->getFieldDataFromRESTObjects($value['value']['bind_value_objects']);
        } elseif (array_key_exists('bind_value_ids', $value) && is_array($value['bind_value_ids'])) {
            return $this->getFieldDataFromArray($value['bind_value_ids']);
        }
        throw new Tracker_FormElement_InvalidFieldValueException('OpenList fields values must be passed as an array of labels (string) in \'bind_value_ids\'');
    }

    private function joinFieldDataFromArray(array $field_data)
    {
        return implode(',', array_filter($field_data));
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string $csv_values
     *
     * @return mixed the field data corresponding to the value for artifact submision
     */
    public function getFieldData($csv_values)
    {
        if (trim($csv_values) != '') {
            return $this->getFieldDataFromArray(explode(',', $csv_values));
        } else {
            return '';
        }
    }

    protected function getFieldDataFromArray(array $values)
    {
        return $this->joinFieldDataFromArray(
            array_map(
                array($this, 'getFieldDataFromStringValue'),
                $values
            )
        );
    }

    private function getFieldDataFromRESTObjects(array $values)
    {
        $data = array();
        foreach ($values as $value) {
            $data[] = $this->getFieldDataFromRESTObject($value);
        }

        return $this->joinFieldDataFromArray($data);
    }

    private function getFieldDataFromRESTObject(array $value)
    {
        return $this->getBind()->getFieldDataFromRESTObject($value, $this);
    }

    protected function getFieldDataFromStringValue($value)
    {
        if ($value == '') {
            return;
        }
        $sv = $this->getBind()->getFieldData($value, false);   // false because we are walking all values one by one
        if ($sv) {
            // existing bind value
            return self::BIND_PREFIX . $sv;
        } else {
            $row = $this->getOpenValueDao()->searchByExactLabel($this->getId(), $value)->getRow();
            if ($row) {
                // existing open value
                return self::OPEN_PREFIX . $row['id'];
            } else {
                // new open value
                return self::NEW_VALUE_PREFIX . $value;
            }
        }
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        $is_valid = $this->getBind()->isvalid($value);
        if (! $is_valid) {
            $null_parameters = new BindParameters($this);
            $error_message   = $this->getBind()->accept($this, $null_parameters);

            $GLOBALS['Response']->addFeedback('error', $error_message);
        }

        return $is_valid;
    }

    /**
     * @return bool true if the value corresponds to none
     */
    public function isNone($value)
    {
        return ($value === null || $value === '');
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitOpenList($this);
    }

    public function getDefaultValue()
    {
        $default_values = parent::getDefaultValue();

        if (! $default_values || $default_values === array(parent::NONE_VALUE)) {
            return '';
        }

        //all default values must be binded
        return self::BIND_PREFIX . implode(',' . self::BIND_PREFIX, $default_values);
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $value = $changeset->getValue($this);
        if ($value) {
            return $value->getFullRESTValue($user);
        }
        return null;
    }

    public function getRESTAvailableValues()
    {
        $type = $this->getBind()->getType();

        if ($type === Tracker_FormElement_Field_List_Bind_Users::TYPE) {
            return array(
                'resource' => array(
                    'type' => 'users',
                    'uri'  => 'users/?query='
                )
            );
        }

        if ($type === Tracker_FormElement_Field_List_Bind_Ugroups::TYPE) {
            $ugroup_manager = new UGroupManager();
            $project        = $this->getTracker()->getProject();
            $user_groups    = $ugroup_manager->getUGroups($project, array(ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::NONE));

            $values = array();
            foreach ($user_groups as $ugroup) {
                $ugroup_representation = new UserGroupRepresentation();
                $ugroup_representation->build($project->getID(), $ugroup);
                $values[] = $ugroup_representation;
            }

            return $values;
        }

        return parent::getRESTAvailableValues();
    }

    /**
     * @return bool
     */
    protected function isPossibleValue($value)
    {
        return true;
    }

    public function visitListBindStatic(
        Tracker_FormElement_Field_List_Bind_Static $bind,
        BindParameters $parameters
    ) {
        return '';
    }

    public function visitListBindUsers(
        Tracker_FormElement_Field_List_Bind_Users $bind,
        BindParameters $parameters
    ) {
        return $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_openlist_value_user_bind', array($this->getLabel()));
    }

    public function visitListBindUgroups(
        Tracker_FormElement_Field_List_Bind_Ugroups $bind,
        BindParameters $parameters
    ) {
        return $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_openlist_value_ugroup_bind', array($this->getLabel()));
    }

    public function visitListBindNull(
        Tracker_FormElement_Field_List_Bind_Null $bind,
        BindParameters $parameters
    ) {
        return '';
    }

    /**
     * @see Tracker_FormElement::process()
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        parent::process($layout, $request, $current_user);

        if ($request->get('func') === 'textboxlist') {
            echo $this->textboxlist($request->get('keyword'), $limit = 10);
        }
    }

    public function getSelectDefaultValues($default_values)
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html  = '';
        $html .= '<p>';
        $html .= '<strong>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'select_default_value') . '</strong><br />';
        $html .= '<div class="textboxlist">
                    <input id="tracker_field_default"
                           name="bind[default][]"
                           style="width:98%"
                           type="text"></div>
                    <input id="field_id" type="hidden"
                           value="' . $this->getId() . '">
                  </div>';

        $html .= '<div class="textboxlist-auto" id="tracker_artifact_textboxlist_default">';
        $html .= '<ul class="feed">';

        $user_manager = UserManager::instance();
        //Field values
        foreach ($default_values as $key => $value) {
            if ($key != 100) {
                $user = $user_manager->getUserById($key);
                if ($user) {
                    $html .= '<li value="' . $user->getId() . '">';
                    $html .= $hp->purify($user->getName());
                    $html .= '</li>';
                }
            }
        }
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</p>';

        return $html;
    }
}
