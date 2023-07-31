<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Date\DateFieldDao;
use Tuleap\Tracker\FormElement\Field\Date\DateValueDao;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\Semantic\Timeframe\ArtifactTimeframeHelper;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_Date extends Tracker_FormElement_Field
{
    public const DEFAULT_VALUE_TYPE_TODAY    = 0;
    public const DEFAULT_VALUE_TYPE_REALDATE = 1;

    public $default_properties = [
        'default_value_type' => [
            'type'    => 'radio',
            'value'   => 0,      //default value is today
            'choices' => [
                'default_value_today' => [
                    'radio_value' => 0,
                    'type'        => 'label',
                    'value'       => 'today',
                ],
                'default_value' => [
                    'radio_value' => 1,
                    'type'  => 'date',
                    'value' => '',
                ],
            ],
        ],
        'display_time' => [
            'value' => 0,
            'type'  => 'checkbox',
        ],
    ];

    /**
     * @throws Tracker_Report_InvalidRESTCriterionException
     */
    public function setCriteriaValueFromREST(Tracker_Report_Criteria $criteria, array $rest_criteria_value)
    {
        $searched_date = $rest_criteria_value[Tracker_Report_REST::VALUE_PROPERTY_NAME];
        $operator      = $rest_criteria_value[Tracker_Report_REST::OPERATOR_PROPERTY_NAME];

        switch ($operator) {
            case Tracker_Report_REST::DEFAULT_OPERATOR:
            case Tracker_Report_REST::OPERATOR_CONTAINS:
            case Tracker_Report_REST::OPERATOR_EQUALS:
                $searched_date = $this->extractStringifiedDate($searched_date);
                if (! $searched_date) {
                    return false;
                }
                $op        = '=';
                $from_date = null;
                $to_date   = $searched_date;
                break;
            case Tracker_Report_REST::OPERATOR_GREATER_THAN:
                $searched_date = $this->extractStringifiedDate($searched_date);
                if (! $searched_date) {
                    return false;
                }
                $op        = '>';
                $from_date = null;
                $to_date   = $searched_date;
                break;
            case Tracker_Report_REST::OPERATOR_LESS_THAN:
                $searched_date = $this->extractStringifiedDate($searched_date);
                if (! $searched_date) {
                    return false;
                }
                $op        = '<';
                $from_date = null;
                $to_date   = $searched_date;
                break;
            case Tracker_Report_REST::OPERATOR_BETWEEN:
                if (! $this->areBetweenDatesValid($searched_date)) {
                    return false;
                }
                $criteria->setIsAdvanced(true);
                $op        = null;
                $from_date = $searched_date[0];
                $to_date   = $searched_date[1];
                break;
            default:
                throw new Tracker_Report_InvalidRESTCriterionException("Invalid operator for criterion field '$this->name' ($this->id). "
                    . "Allowed operators: [" . implode(' | ', [
                        Tracker_Report_REST::OPERATOR_EQUALS,
                        Tracker_Report_REST::OPERATOR_GREATER_THAN,
                        Tracker_Report_REST::OPERATOR_LESS_THAN,
                        Tracker_Report_REST::OPERATOR_BETWEEN,
                    ]) . "]");
        }

        $criteria_value           = [
            'op'        => $op,
            'from_date' => $from_date,
            'to_date'   => $to_date,
        ];
        $formatted_criteria_value = $this->getFormattedCriteriaValue($criteria_value);

        $this->setCriteriaValue($formatted_criteria_value, $criteria->report->id);
        return true;
    }

    private function extractStringifiedDate($date)
    {
        if (is_array($date) && count($date) == 1 && isset($date[0])) {
            $date = $date[0];
        }

        if (! strtotime($date)) {
            return null;
        }

        return $date;
    }

    private function areBetweenDatesValid($criteria_dates)
    {
        return is_array($criteria_dates)
            && count($criteria_dates) == 2
            && isset($criteria_dates[0]) && strtotime($criteria_dates[0])
            && isset($criteria_dates[1]) && strtotime($criteria_dates[1]);
    }

    public function canBeUsedToSortReport()
    {
        return true;
    }

    /**
     * Continue the initialisation from an xml (FormElementFactory is not smart enough to do all stuff.
     * Polymorphism rulez!!!
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored (and values)
     *
     * @return void
     */
    public function continueGetInstanceFromXML(
        $xml,
        &$xmlMapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        TrackerXmlImportFeedbackCollector $feedback_collector,
    ) {
        parent::continueGetInstanceFromXML($xml, $xmlMapping, $user_finder, $feedback_collector);

        // add children
        if (isset($this->default_properties['default_value'])) {
            if ($this->default_properties['default_value'] === 'today') {
                $this->default_properties['default_value_type']['value'] = self::DEFAULT_VALUE_TYPE_TODAY;
            } else {
                $this->default_properties['default_value_type']['value']                             = self::DEFAULT_VALUE_TYPE_REALDATE;
                $this->default_properties['default_value_type']['choices']['default_value']['value'] = $this->default_properties['default_value'];
            }
            unset($this->default_properties['default_value']);
        } else {
            $this->default_properties['default_value_type']['value']                             = self::DEFAULT_VALUE_TYPE_REALDATE;
            $this->default_properties['default_value_type']['choices']['default_value']['value'] = '';
        }
    }

    /**
     * Export form element properties into a SimpleXMLElement
     *
     * @param SimpleXMLElement &$root The root element of the form element
     *
     * @return void
     */
    public function exportPropertiesToXML(&$root)
    {
        $child = $root->addChild('properties');

        foreach ($this->getProperties() as $name => $property) {
            if ($name === 'default_value_type') {
                $this->exportDefaultValueToXML($child, $property);
                continue;
            }

            $this->exportDisplayTimeToXML($child);
        }
    }

    private function exportDefaultValueToXML(SimpleXMLElement &$xml_element, array $property)
    {
        $value_type = $property['value'];
        if ($value_type == '1') {
            // a date
            $prop = $property['choices']['default_value'];
            if (! empty($prop['value'])) {
                // a specific date
                $xml_element->addAttribute('default_value', $prop['value']);
            } // else no default value, nothing to do
        } else {
            // today
            $prop = $property['choices']['default_value_today'];
            // $prop['value'] is the string 'today'
            $xml_element->addAttribute('default_value', $prop['value']);
        }
    }

    private function exportDisplayTimeToXML(SimpleXMLElement &$xml_element)
    {
        $xml_element->addAttribute('display_time', $this->isTimeDisplayed() ? '1' : '0');
    }

    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    public function getDefaultValue()
    {
        if ($this->getProperty('default_value_type')) {
            $value = $this->formatDate(parent::getDefaultValue());
        } else { //Get date of the current day
            $value = $this->formatDate($_SERVER['REQUEST_TIME']);
        }
        return $value;
    }

    /**
     * Return the Field_Date_Dao
     *
     * @return DateFieldDao The dao
     */
    protected function getDao()
    {
        return new DateFieldDao();
    }

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     *
     * @return bool true if success
     */
    public function delete()
    {
        return $this->getDao()->delete($this->id);
    }

    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if (! $this->isUsed()) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        //Only filter query if criteria is valuated
        $criteria_value = $this->getCriteriaValue($criteria);

        if (count($criteria_value) === 0) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        $a = 'A_' . $this->id;
        $b = 'B_' . $this->id;

        $compare_date_stmt = $this->getSQLCompareDate(
            (bool) $criteria->is_advanced,
            $criteria_value['op'],
            $criteria_value['from_date'],
            $criteria_value['to_date'],
            $b . '.value'
        );
        return Option::fromValue(
            ParametrizedFromWhere::fromParametrizedFrom(
                new ParametrizedFrom(
                    " INNER JOIN tracker_changeset_value AS $a
                                 ON ($a.changeset_id = c.id AND $a.field_id = ? )
                                 INNER JOIN tracker_changeset_value_date AS $b
                                 ON ($a.id = $b.changeset_value_id
                                     AND $compare_date_stmt->sql
                                 ) ",
                    [
                        $this->id,
                        ...$compare_date_stmt->parameters,
                    ]
                )
            )
        );
    }

     /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_Report_Criteria $criteria
     */
    public function getCriteriaValue($criteria): array
    {
        if (! isset($this->criteria_value)) {
            $this->criteria_value = [];
        }

        if (! isset($this->criteria_value[$criteria->report->id])) {
            $this->criteria_value[$criteria->report->id] = [];
            $dao                                         = $this->getCriteriaDao();
            if ($dao && $row = $dao->searchByCriteriaId($criteria->id)->getRow()) {
                $this->criteria_value[$criteria->report->id]['op']        = $row['op'];
                $this->criteria_value[$criteria->report->id]['from_date'] = $row['from_date'];
                $this->criteria_value[$criteria->report->id]['to_date']   = $row['to_date'];
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
        if (empty($value['to_date']) && empty($value['from_date'])) {
            return '';
        } else {
            //from date
            if (empty($value['from_date'])) {
                $value['from_date'] = 0;
            } else {
                 $value['from_date'] = strtotime($value['from_date']);
            }

            //to date
            if (empty($value['to_date'])) {
                $value['to_date'] = 0;
            } else {
                 $value['to_date'] = strtotime($value['to_date']);
            }

            //Operator
            if (empty($value['op']) || ($value['op'] !== '<' && $value['op'] !== '=' && $value['op'] !== '>')) {
                $value['op'] = '=';
            }

            return $value;
        }
    }

    /**
     * Build the sql statement for date comparison
     *
     * @param bool   $is_advanced Are we in advanced mode ?
     * @param string $op          The operator used for the comparison (not for advanced mode)
     * @param int    $from        The $from date used for comparison (only for advanced mode)
     * @param int    $to          The $to date used for comparison
     * @param string $column      The column to look into. ex: "A_234.value" | "c.submitted_on" ...
     */
    protected function getSQLCompareDate($is_advanced, $op, $from, $to, $column): ParametrizedSQLFragment
    {
        return $this->getSQLCompareDay($is_advanced, $op, $from, $to, $column);
    }

    private function getSQLCompareDay($is_advanced, $op, $from, $to, $column): ParametrizedSQLFragment
    {
        $seconds_in_a_day = DateHelper::SECONDS_IN_A_DAY;

        if ($is_advanced) {
            if (! $to) {
                $to = $_SERVER['REQUEST_TIME'];
            }
            if (empty($from)) {
                return new ParametrizedSQLFragment(
                    "$column <= ?",
                    [$to + $seconds_in_a_day - 1]
                );
            }

            return new ParametrizedSQLFragment(
                "$column BETWEEN ? AND ?",
                [$from, $to + $seconds_in_a_day - 1]
            );
        }

        return match ($op) {
            '<' => new ParametrizedSQLFragment("$column < ?", [$to]),
            '=' => new ParametrizedSQLFragment("$column BETWEEN ? AND ?", [$to, $to + $seconds_in_a_day - 1]),
            default => new ParametrizedSQLFragment("$column > ?", [$to + $seconds_in_a_day - 1]),
        };
    }

    public function getQuerySelect(): string
    {
        $R2 = 'R2_' . $this->id;
        return "$R2.value AS " . $this->getQuerySelectName();
    }

    public function getQueryFrom()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;

        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN tracker_changeset_value_date AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->id . " )";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby(): string
    {
        if (! $this->isUsed()) {
            return '';
        }
        $R2 = 'R2_' . $this->id;
        return "$R2.value";
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Date_ValueDao();
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        return $this->formatDateForDisplay($value);
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        return $this->formatDateForCSV($value);
    }

    public function fetchAdvancedCriteriaValue($criteria)
    {
        $hp             = Codendi_HTMLPurifier::instance();
        $html           = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        $html          .= '<div style="text-align:right">';
        $value          = isset($criteria_value['from_date']) ? $this->formatDateForReport($criteria_value['from_date']) : '';
        $html          .= '<label>';
        $html          .= dgettext('tuleap-tracker', 'Start') . ' ';
        $html          .= $GLOBALS['HTML']->getBootstrapDatePicker(
            "criteria_" . $this->id . "_from",
            "criteria[" . $this->id . "][from_date]",
            $value,
            [],
            [],
            false,
            'date-time-' . $this->getName()
        );
        $html          .= '</label>';
        $value          = isset($criteria_value['to_date']) ? $this->formatDateForReport($criteria_value['to_date']) : '';
        $html          .= '<label>';
        $html          .= dgettext('tuleap-tracker', 'End') . ' ';
        $html          .= $GLOBALS['HTML']->getBootstrapDatePicker(
            "criteria_" . $this->id . "_to",
            "criteria[" . $this->id . "][to_date]",
            $value,
            [],
            [],
            false,
            'date-time-' . $this->getName()
        );
        $html          .= '</label>';
        $html          .= '</div>';
        return $html;
    }

    public function fetchCriteriaValue($criteria)
    {
        $html = '';
        if ($criteria->is_advanced) {
            $html = $this->fetchAdvancedCriteriaValue($criteria);
        } else {
            $hp             = Codendi_HTMLPurifier::instance();
            $criteria_value = $this->getCriteriaValue($criteria);
            $lt_selected    = '';
            $eq_selected    = '';
            $gt_selected    = '';
            if ($criteria_value) {
                if ($criteria_value['op'] == '<') {
                    $lt_selected = 'selected="selected"';
                } elseif ($criteria_value['op'] == '>') {
                    $gt_selected = 'selected="selected"';
                } else {
                    $eq_selected = 'selected="selected"';
                }
            } else {
                $eq_selected = 'selected="selected"';
            }
            $html .= '<div style="white-space:nowrap;">';

            $criteria_selector = [
                "name"      => 'criteria[' . $this->id . '][op]',
                "criterias" => [
                    ">" => [
                        "html_value" => dgettext('tuleap-tracker', 'After'),
                        "selected"   => $gt_selected,

                    ],
                    "=" => [
                        "html_value" => dgettext('tuleap-tracker', 'As of'),
                        "selected"   => $eq_selected,
                    ],
                    "<" => [
                        "html_value" => dgettext('tuleap-tracker', 'Before'),
                        "selected"   => $lt_selected,
                    ],
                ],
            ];

            $value = $criteria_value ? $this->formatDateForReport($criteria_value['to_date']) : '';

            $html .= $GLOBALS['HTML']->getBootstrapDatePicker(
                "tracker_report_criteria_" . $this->id,
                "criteria[" . $this->id . "][to_date]",
                $value,
                $criteria_selector,
                [],
                false,
                'date-time-' . $this->getName()
            );
            $html .= '</div>';
        }
        return $html;
    }

    private function formatDateForReport($criteria_value)
    {
        $date_formatter = new Tracker_FormElement_DateFormatter($this);
        return $date_formatter->formatDate($criteria_value);
    }

    public function fetchMasschange()
    {
    }

    /**
     * Format a timestamp into Y-m-d H:i format
     */
    protected function formatDateTime($date)
    {
        return format_date(Tracker_FormElement_DateTimeFormatter::DATE_TIME_FORMAT, (float) $date, '');
    }

    /**
     * Returns the CSV date format of the user regarding its preferences
     * Returns either 'month_day_year' or 'day_month_year'
     *
     * @return string the CSV date format of the user regarding its preferences
     */
    public function _getUserCSVDateFormat() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $user = UserManager::instance()->getCurrentUser();
        return (string) $user->getPreference('user_csv_dateformat');
    }

    protected function formatDateForCSV($date)
    {
        $date_csv_export_pref = $this->_getUserCSVDateFormat();
        switch ($date_csv_export_pref) {
            case "month_day_year":
                $fmt = 'm/d/Y';
                break;
            case "day_month_year":
                $fmt = 'd/m/Y';
                break;
            default:
                $fmt = 'm/d/Y';
                break;
        }

        if ($this->isTimeDisplayed()) {
            $fmt .= ' H:i';
        }

        return format_date($fmt, (float) $date, '');
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
        return $this->formatDate($value);
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        $value = 0;
        if ($v = $changeset->getValue($this)) {
            if ($row = $this->getValueDao()->searchById($v->getId(), $this->id)->getRow()) {
                $value = $row['value'];
            }
        }
        return $this->formatDate($value);
    }

    protected function getValueDao()
    {
        return new DateValueDao();
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $errors = $this->has_errors ? ['has_error'] : [];

        return $this->getFormatter()->fetchSubmitValue($submitted_values, $errors);
    }

     /**
     * Fetch the html code to display the field value in masschange submission form
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        return $this->getFormatter()->fetchSubmitValueMasschange();
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
        $errors = $this->has_errors ? ['has_error'] : [];

        return $this->getFormatter()->fetchArtifactValue($value, $submitted_values, $errors);
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
        if (empty($value) || ! $value->getTimestamp()) {
            return '-';
        }
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    public function getNoValueLabel()
    {
        return parent::getNoValueLabel();
    }

    public function getValueFromSubmitOrDefault(array $submitted_values)
    {
        return parent::getValueFromSubmitOrDefault($submitted_values);
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
        $timeframe_helper = $this->getArtifactTimeframeHelper();
        $html_value       = $this->getFormatter()->fetchArtifactValueReadOnly($artifact, $value);
        $user             = $this->getCurrentUser();

        if (
            $timeframe_helper->artifactHelpShouldBeShownToUser($user, $this) &&
            $value &&
            $value instanceof Tracker_Artifact_ChangesetValue_Date &&
            $value->getTimestamp() !== null
        ) {
            $html_value = $html_value
                . '<span class="artifact-timeframe-helper"> ('
                . $timeframe_helper->getDurationArtifactHelperForReadOnlyView($user, $artifact)
                . ')</span>';
        }

        return $html_value;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            "tracker_admin_field_" . $this->id,
            '',
            $this->hasDefaultValue() ? $this->getDefaultValue() : '',
            [],
            [],
            $this->isTimeDisplayed(),
            'date-time-' . $this->getName()
        );
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Date');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Allows user to select a date with a calendar');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('calendar/cal.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('calendar/cal--plus.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue_Date $value The changeset value for this field
     * @return string
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if ($value && $value->getTimestamp()) {
            $user  = HTTPRequest::instance()->getCurrentUser();
            $html .= $this->isTimeDisplayed()
                ? DateHelper::relativeDateInlineContext($value->getTimestamp() ?? 0, $user)
                : DateHelper::relativeDateInlineContextWithoutTime($value->getTimestamp() ?? 0, $user);
        }
        return $html;
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
        return $this->getFormatter()->validate($value);
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        return $this->getValueDao()->create($changeset_value_id, strtotime($value));
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return strtotime($this->formatDate($old_value->getTimestamp())) != strtotime($new_value);
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
        if ($row = $this->getValueDao()->searchById($value_id, $this->id)->getRow()) {
            $changeset_value = new Tracker_Artifact_ChangesetValue_Date($value_id, $changeset, $this, $has_changed, $row['value']);
        }
        return $changeset_value;
    }

    /**
     * Get available values of this field for REST usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getRESTAvailableValues()
    {
        return null;
    }

    /**
     * Compute the number of digits of an int (could be private but I want to unit test it)
     * 1 => 1
     * 12 => 2
     * 123 => 3
     * 1999 => 4
     * etc.
     *
     */
    public function _nbDigits($int_value) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return 1 + (int) (log($int_value) / log(10));
    }

    /**
     * Explode a date in the form of (m/d/Y H:i or d/m/Y H:i) regarding the csv peference
     * into its a list of 5 parts (YYYY,MM,DD,H,i)
     * if DD and MM are not defined then default them to 1
     *
     *
     * Please use function date_parse_from_format instead
     * when codendi will run PHP >= 5.3
     *
     *
     * @param string $date the date in the form of m/d/Y H:i or d/m/Y H:i
     *
     * @return array the five parts of the date array(YYYY,MM,DD,H,i)
     */
    public function explodeXlsDateFmt($date)
    {
        $user_preference = $this->_getUserCSVDateFormat();
        $match           = [];

        if (preg_match("/\s*(\d+)\/(\d+)\/(\d+) (\d+):(\d+)(?::(\d+))?/", $date, $match)) {
            return $this->getCSVDateComponantsWithHours($match, $user_preference);
        } elseif (preg_match("/\s*(\d+)\/(\d+)\/(\d+)/", $date, $match)) {
            return $this->getCSVDateComponantsWithoutHours($match, $user_preference);
        }

        return $this->getCSVDefaultDateComponants();
    }

    /**
     * @return array
     */
    private function getCSVWellFormedDateComponants($month, $day, $year, $hour, $minute, $second)
    {
        if (checkdate($month, $day, $year) && $this->_nbDigits($year) ===  4) {
            return [$year, $month, $day, $hour, $minute, $second];
        }

        return [];
    }

    private function getCSVDateComponantsWithoutHours(array $match, $user_preference)
    {
        $hour   = '0';
        $minute = '0';
        $second = '0';

        if ($user_preference == "day_month_year") {
            [, $day, $month, $year] = $match;
        } else {
            [, $month, $day, $year] = $match;
        }

        return $this->getCSVWellFormedDateComponants($month, $day, $year, $hour, $minute, $second);
    }

    private function getCSVDateComponantsWithHours(array $match, $user_preference)
    {
        if ($user_preference == "day_month_year") {
            [, $day, $month, $year, $hour, $minute] = $match;
        } else {
            [, $month, $day, $year, $hour, $minute] = $match;
        }

        return $this->getCSVWellFormedDateComponants($month, $day, $year, $hour, $minute, '00');
    }

    private function getCSVDefaultDateComponants()
    {
        $year   = '1970';
        $month  = '1';
        $day    = '1';
        $hour   = '0';
        $minute = '0';
        $second = '0';

        return $this->getCSVWellFormedDateComponants($month, $day, $year, $hour, $minute, $second);
    }

    /**
     * Get the field data for CSV import
     *
     * @param string $data_cell the CSV field value (a date with the form dd/mm/YYYY or mm/dd/YYYY)
     *
     * @return string the date with the form YYYY-mm-dd corresponding to the date $data_cell, or null if date format is wrong or empty
     */
    public function getFieldDataForCSVPreview($data_cell)
    {
        if ($data_cell !== '') {
            $date_explode = $this->explodeXlsDateFmt($data_cell);
            if (isset($date_explode[0])) {
                if ($this->_nbDigits($date_explode[0]) == 4) {
                    return $this->getFormatter()->getFieldDataForCSVPreview($date_explode);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string $value
     *
     * @return String the field data corresponding to the value for artifact submision, or null if date format is wrong
     */
    public function getFieldData($value)
    {
        if (strpos($value, '/') !== false) {
            // Assume the format is either dd/mm/YYYY or mm/dd/YYYY depending on the user preferences.
            return $this->getFieldDataForCSVPreview($value);
        }

        if (strpos($value, '-') !== false) {
            // Assume the format is YYYY-mm-dd
            $first_space_position = strpos($value, " ");
            if ($first_space_position !== false) {
                $value_without_time = substr(
                    $value,
                    0,
                    $first_space_position,
                );
            } else {
                $value_without_time = $value;
            }

            $date_array = explode('-', $value_without_time);
            $year       = (int) $date_array[0];
            $month      = (int) $date_array[1];
            $day        = (int) $date_array[2];

            if (count($date_array) === 3 && checkdate($month, $day, $year) && $this->_nbDigits($year)) {
                return $value;
            }

            return null;
        }

        if ((int) $value == $value) {
            // Assume it's a timestamp
            return $this->getFormatter()->formatDate((int) $value);
        }

        if (trim($value) === '') {
            return '';
        }

        return null;
    }

    /**
     * Convert ISO8601 into internal date needed by createNewChangeset
     *
     * @param array $value
     */
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        if (! $value['value']) {
            return '';
        }

        if ($this->isTimeDisplayed()) {
            return date(Tracker_FormElement_DateTimeFormatter::DATE_TIME_FORMAT, strtotime($value['value']));
        }

        return date(Tracker_FormElement_DateFormatter::DATE_FORMAT, strtotime($value['value']));
    }

    public function getFieldDataFromRESTValueByField(array $value, ?Artifact $artifact = null)
    {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

    /**
     * Return the field last value
     *
     *
     * @return string|false
     */
    public function getLastValue(Artifact $artifact)
    {
        return $artifact->getValue($this)->getValue();
    }

    /**
     * Get artifacts that responds to some criteria
     *
     * @param date    $date      The date criteria
     * @param int $trackerId The Tracker Id
     *
     * @return Array
     */
    public function getArtifactsByCriterias($date, $trackerId = null)
    {
        $artifacts = [];
        $dao       = new DateValueDao();
        $dar       = $dao->getArtifactsByFieldAndValue($this->id, $date);
        if ($dar && ! $dar->isError()) {
            $artifactFactory = Tracker_ArtifactFactory::instance();
            foreach ($dar as $row) {
                $artifacts[] = $artifactFactory->getArtifactById($row['artifact_id']);
            }
        }
        return $artifacts;
    }

    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifactReadOnly($artifact, $submitted_values);
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitDate($this);
    }

    public function isTimeDisplayed()
    {
        return ($this->getProperty('display_time') == 1);
    }

    public function formatDate($date)
    {
        return $this->getFormatter()->formatDate($date);
    }

    public function formatDateForDisplay($timestamp)
    {
        return $this->getFormatter()->formatDateForDisplay($timestamp);
    }

    /**
     * @return Tracker_FormElement_DateFormatter
     */
    public function getFormatter()
    {
        if ($this->isTimeDisplayed()) {
            return new Tracker_FormElement_DateTimeFormatter($this);
        }

        return new Tracker_FormElement_DateFormatter($this);
    }

    protected function getArtifactTimeframeHelper(): ArtifactTimeframeHelper
    {
        return new ArtifactTimeframeHelper(
            SemanticTimeframeBuilder::build(),
            \BackendLogger::getDefaultLogger()
        );
    }
}
