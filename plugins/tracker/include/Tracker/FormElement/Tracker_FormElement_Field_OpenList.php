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

use Tuleap\Option\Option;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueUnchanged;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListFieldDao;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListChangesetValueDao;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_OpenList extends Tracker_FormElement_Field_List implements BindVisitor
{
    public const BIND_PREFIX      = 'b';
    public const OPEN_PREFIX      = 'o';
    public const NEW_VALUE_PREFIX = '!';

    public $default_properties = [
        'hint' => [
            'value' => 'Type in a search term',
            'type'  => 'string',
            'size'  => 40,
        ],
    ];

    public function isMultiple(): bool
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
    public function fetchOpenList($values = [], $name = true)
    {
        $hp   = Codendi_HTMLPurifier::instance();
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
    public function fetchOpenListMasschange($values = [], $name = true)
    {
        $hp   = Codendi_HTMLPurifier::instance();
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
     * @param Artifact                        $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        assert($value instanceof Tracker_Artifact_ChangesetValue_List);
        $selected_values = $value ? $value->getListValues() : [];
        if (is_array($submitted_values) && isset($submitted_values[$this->id])) {
            return $this->fetchOpenList($this->toObj($submitted_values[$this->id]));
        }
        return $this->fetchOpenList($selected_values);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact                        $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $labels          = [];
        $selected_values = $value ? $value->getListValues() : [];

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
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Fetch data to display the field value in mail
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     * @param string                          $format   output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text',
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
                $selected_values = [];
                if ($value !== null) {
                    $selected_values = $value->getListValues();
                }
                foreach ($selected_values as $value) {
                    if ($value->getId() != 100) {
                        $output .= $value->getLabel();
                    }
                }
                break;
        }
        return $output;
    }

    private function textboxlist($keyword, $limit = 10): array
    {
        $json_values     = [];
        $matching_values = $this->getBind()->getValuesByKeyword($keyword, $limit);
        $nb              = count($matching_values);
        if ($nb < $limit) {
            foreach ($this->getOpenValueDao()->searchByKeyword($this->getId(), $keyword, $limit - $nb) as $row) {
                $matching_values[] = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['label'],
                    $row['is_hidden']
                );
            }
        }
        foreach ($matching_values as $v) {
            $json_values[] = $v->fetchForOpenListJson();
        }
        return $json_values;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $has_name = false;
        return $this->fetchOpenList($this->getDefaultValues(), $has_name);
    }

    /**
     * Return the dao
     *
     * @return OpenListFieldDao The dao
     */
    protected function getDao()
    {
        return new OpenListFieldDao();
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Open List');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Provides a textbox containing a list of values, with autocompletion');
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
        return new OpenListChangesetValueDao();
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
        $value_ids       = $this->getValueDao()->searchById($value_id, $this->id);
        $bindvalue_ids   = [];
        foreach ($value_ids as $v) {
            if ($v['bindvalue_id']) {
                $bindvalue_ids[] = $v['bindvalue_id'];
            }
        }
        $bind_values = [];
        if (count($bindvalue_ids)) {
            $bind_values = $this->getBind()->getBindValuesForIds($bindvalue_ids);
        }

        $list_values = [];
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
        return new OpenListValueDao();
    }

    protected $cache_openvalues = [];

    public function getOpenValueById($oid)
    {
        if (! isset($this->cache_openvalues[$oid])) {
            $this->cache_openvalues[$oid] = null;
            if ($row = $this->getOpenValueDao()->searchById($this->getId(), $oid)->getRow()) {
                $this->cache_openvalues[$oid] = new Tracker_FormElement_Field_List_OpenValue(
                    $row['id'],
                    $row['label'],
                    $row['is_hidden']
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
        CreatedFileURLMapping $url_mapping,
    ) {
        $openvalue_dao = $this->getOpenValueDao();
        // the separator is a comma
        $values    = $this->sanitize($value);
        $value_ids = [];
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
                $value_ids[] = [
                    'bindvalue_id' => $bindvalue_id,
                    'openvalue_id' => $openvalue_id,
                ];
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
        $values    = explode(',', (string) $submitted_value);
        $sanitized = [];
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
        $sanitized = [];
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
                            $v           = $this->getOpenValueById($openvalue_id);
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
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return $old_value->getValue() != $this->sanitize($new_value);
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        $arr       = [];
        $bindtable = $this->getBind()->getBindtableSqlFragment();
        $values    = $this->getDao()->searchChangesetValues(
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
                    $row['openvalue_label'],
                    $row['openvalue_is_hidden']
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
    public function fetchCardValue(Artifact $artifact, ?Tracker_CardDisplayPreferences $display_preferences = null)
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
        $arr       = [];
        $bindtable = $this->getBind()->getBindtableSqlFragment();
        $values    = $this->getDao()->searchChangesetValues(
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
                    $row['openvalue_label'],
                    $row['openvalue_is_hidden']
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

    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            $criteria_value = $this->extractCriteriaValue($this->getCriteriaValue($criteria));
            $openvalues     = [];
            $bindvalues     = [];
            foreach ($criteria_value as $v) {
                if ($v instanceof \Tracker_FormElement_Field_List_UnsavedValue) {
                    //ignore it
                } elseif ($v instanceof \Tracker_FormElement_Field_List_OpenValue) {
                    $openvalues[] = $v->getId();
                } else { //bindvalue
                    $bindvalues[] = $v->getId();
                }
            }
            //Only filter query if criteria is valuated
            if ($openvalues || $bindvalues) {
                $a         = 'A_' . $this->id;
                $b         = 'B_' . $this->id;
                $statement = new ParametrizedSQLFragment('1', []);
                if ($openvalues) {
                    $in        = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $openvalues);
                    $statement = new ParametrizedSQLFragment("$b.openvalue_id IN ($in)", $in->values());
                }
                if ($bindvalues) {
                    $in        = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', $bindvalues);
                    $statement = new ParametrizedSQLFragment(
                        $statement->sql . ' OR ' . "$b.bindvalue_id IN ($in)",
                        [
                            ...$statement->parameters,
                            ...$in->values(),
                        ]
                    );
                }
                return Option::fromValue(
                    new ParametrizedFrom(
                        " INNER JOIN tracker_changeset_value AS $a
                         ON ($a.changeset_id = c.id
                             AND $a.field_id = ?
                         )
                         INNER JOIN tracker_changeset_value_openlist AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND ($statement->sql)
                         )
                         ",
                        [
                            $this->id,
                            ...$statement->parameters,
                        ]
                    )
                );
            }
        }

        return Option::nothing(ParametrizedFrom::class);
    }

    protected function formatCriteriaValue($value_to_match)
    {
        return 'b' . $value_to_match;
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedSQLFragment::class);
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
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_Report_Criteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria)
    {
        if (! isset($this->criteria_value)) {
            $this->criteria_value = [];
        }

        if (! isset($this->criteria_value[$criteria->report->id])) {
            $this->criteria_value[$criteria->report->id] = '';
            $dao                                         = $this->getCriteriaDao();
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
        $hp             = Codendi_HTMLPurifier::instance();
        $html           = '';
        $criteria_value = $this->extractCriteriaValue($this->getCriteriaValue($criteria));

        $name = "criteria[$this->id]";
        return $this->fetchOpenList($criteria_value, $name);
    }

    public function extractCriteriaValue($criteria_value): array
    {
        //switch to array
        if (! is_array($criteria_value)) {
            $criteria_value = explode(',', $criteria_value);
        }

        //first extract open and unsaved values
        $bindvalue_ids = [];
        foreach ($criteria_value as $key => $val) {
            $val = trim($val);
            if (! $val) {
                unset($criteria_value[$key]);
            } elseif ($val[0] === self::OPEN_PREFIX) {
                if ($v = $this->getOpenValueById(substr($val, 1))) {
                    $criteria_value[$key] = $v;
                } else {
                    unset($criteria_value[$key]);
                }
            } elseif ($val[0] === self::BIND_PREFIX) {
                $bindvalue_ids[]      = substr($val, 1);
                $criteria_value[$key] = $val; //store the trimmed val
            } elseif ($val[0] === self::NEW_VALUE_PREFIX) {
                $criteria_value[$key] = new Tracker_FormElement_Field_List_UnsavedValue(substr($val, 1));
            } else {
                unset($criteria_value[$key]);
            }
        }

        //load bind values
        $bind_values = [];
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

    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
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
                [$this, 'getFieldDataFromStringValue'],
                $values
            )
        );
    }

    private function getFieldDataFromRESTObjects(array $values)
    {
        $data = [];
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
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
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
     * Validate a required field
     *
     * @param Artifact $artifact The artifact to check
     * @param mixed    $value    The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Artifact $artifact, $value)
    {
        $this->has_errors = false;

        if ($this->isEmpty($value, $artifact) && $this->isRequired()) {
            $this->addRequiredError();
            return false;
        }

        $invalid_values   = [];
        $nb_filled_values = 0;
        $extracted_values = explode(',', (string) $value);
        foreach ($extracted_values as $extracted_value) {
            $extracted_value = trim($extracted_value);
            if (! $extracted_value) {
                continue;
            }

            switch ($extracted_value[0]) {
                case self::BIND_PREFIX: // bind value
                case self::OPEN_PREFIX: // open value
                    if ((int) substr($extracted_value, 1)) {
                        $nb_filled_values++;
                    }
                    break;
                case self::NEW_VALUE_PREFIX: // new open value
                    if (substr($extracted_value, 1) !== "") {
                        $nb_filled_values++;
                    }
                    break;
                default:
                    $invalid_values[] = $extracted_value;
                    break;
            }
        }

        if (! empty($invalid_values)) {
            $GLOBALS['Response']->addFeedback(
                'error',
                sprintf(
                    dngettext(
                        'tuleap-tracker',
                        'Invalid value %s for field %s.',
                        'Invalid values %s for field %s.',
                        count($invalid_values),
                    ),
                    implode(', ', $invalid_values),
                    $this->getLabel() . ' (' . $this->getName() . ')'
                )
            );
        }

        if ($this->isRequired() && $nb_filled_values === 0) {
            $this->addRequiredError();
        }

        return ! $this->has_errors;
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

        if (! $default_values || $default_values === [parent::NONE_VALUE]) {
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
            return [
                'resource' => [
                    'type' => 'users',
                    'uri'  => 'users/?query=',
                ],
            ];
        }

        if ($type === Tracker_FormElement_Field_List_Bind_Ugroups::TYPE) {
            $ugroup_manager = new UGroupManager();
            $project        = $this->getTracker()->getProject();
            $user_groups    = $ugroup_manager->getUGroups($project, [ProjectUGroup::ANONYMOUS, ProjectUGroup::REGISTERED, ProjectUGroup::NONE]);

            $values = [];
            foreach ($user_groups as $ugroup) {
                $ugroup_representation = new MinimalUserGroupRepresentation($project->getID(), $ugroup);
                $values[]              = $ugroup_representation;
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
        BindParameters $parameters,
    ) {
        return '';
    }

    public function visitListBindUsers(
        Tracker_FormElement_Field_List_Bind_Users $bind,
        BindParameters $parameters,
    ) {
        return sprintf(dgettext('tuleap-tracker', '%1$s contains a value which is not a login or an email.'), $this->getLabel());
    }

    public function visitListBindUgroups(
        Tracker_FormElement_Field_List_Bind_Ugroups $bind,
        BindParameters $parameters,
    ) {
        return sprintf(dgettext('tuleap-tracker', '%1$s contains a value which is not a user group.'), $this->getLabel());
    }

    public function visitListBindNull(
        Tracker_FormElement_Field_List_Bind_Null $bind,
        BindParameters $parameters,
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
            $GLOBALS['Response']->sendJSON($this->textboxlist($request->get('keyword'), $limit = 10));
            exit();
        }
    }

    public function getSelectDefaultValues($default_values)
    {
        if (! $this->getBind() instanceof Tracker_FormElement_Field_List_Bind_Users) {
            return parent::getSelectDefaultValues($default_values);
        }

        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '';
        $html .= '<p>';
        $html .= '<strong>' . dgettext('tuleap-tracker', 'Select default value') . '</strong><br />';
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
                    $html .= $hp->purify($user->getUserName());
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
