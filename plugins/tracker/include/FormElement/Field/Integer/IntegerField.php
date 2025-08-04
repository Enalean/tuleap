<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Integer;

use Codendi_HTMLPurifier;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_FieldVisitor;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tracker_Report_Criteria_Int_ValueDao;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\NumericField;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\IntegerFieldSpecificPropertiesDAO;
use Tuleap\Tracker\Report\Criteria\CriteriaAlphaNumValueDAO;
use Tuleap\Tracker\Report\Criteria\DeleteReportCriteriaValue;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

class IntegerField extends NumericField
{
    #[\Override]
    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if (! $this->isUsed()) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        //Only filter query if criteria is valuated
        $criteria_value = $this->getCriteriaValue($criteria);

        if ($criteria_value === '' || $criteria_value === null) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        $a = 'A_' . $this->id;
        $b = 'B_' . $this->id;

        return $this->buildMatchExpression("$b.value", $criteria_value)->mapOr(
            function (ParametrizedSQLFragment $match_expression) use ($a, $b) {
                return Option::fromValue(
                    ParametrizedFromWhere::fromParametrizedFrom(
                        new ParametrizedFrom(
                            " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ? )
                             INNER JOIN tracker_changeset_value_int AS $b ON (
                                $b.changeset_value_id = $a.id
                                AND " . $match_expression->sql . '
                             ) ',
                            [
                                $this->id,
                                ...$match_expression->parameters,
                            ],
                        )
                    )
                );
            },
            Option::nothing(ParametrizedFromWhere::class)
        );
    }

    #[\Override]
    public function fetchCriteriaValue(Tracker_Report_Criteria $criteria): string
    {
        $html           = '<input data-test="integer-report-criteria" type="text" name="criteria[' . $this->id . ']" id="tracker_report_criteria_' . $this->id . '" value="';
        $criteria_value = $this->getCriteriaValue($criteria);

        if ($criteria_value !== '') {
            $html_purifier = Codendi_HTMLPurifier::instance();
            $html         .= $html_purifier->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }

        $html .= '" />';
        return $html;
    }

    #[\Override]
    public function getQueryFrom()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;

        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN tracker_changeset_value_int AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->id . ' )';
    }

    #[\Override]
    protected function buildMatchExpression(string $field_name, $criteria_value): Option
    {
        return parent::buildMatchExpression($field_name, $criteria_value);
    }

    #[\Override]
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Int_ValueDao();
    }

    #[\Override]
    public function getDeleteCriteriaValueDAO(): DeleteReportCriteriaValue
    {
        return new CriteriaAlphaNumValueDAO();
    }

    #[\Override]
    public function canBeUsedToSortReport()
    {
        return true;
    }

    #[\Override]
    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?array $redirection_parameters = null,
    ): string {
        return (string) $value;
    }

    #[\Override]
    protected function getValueDao()
    {
        return new IntegerValueDao();
    }

    #[\Override]
    protected function getDuplicateSpecificPropertiesDao(): IntegerFieldSpecificPropertiesDAO
    {
        return new IntegerFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getDeleteSpecificPropertiesDao(): IntegerFieldSpecificPropertiesDAO
    {
        return new IntegerFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getSearchSpecificPropertiesDao(): IntegerFieldSpecificPropertiesDAO
    {
        return new IntegerFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getSaveSpecificPropertiesDao(): IntegerFieldSpecificPropertiesDAO
    {
        return new IntegerFieldSpecificPropertiesDAO();
    }

    #[\Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Integer');
    }

    #[\Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A textfield wich accept only integers');
    }

    #[\Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field-int.png');
    }

    #[\Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field-int--plus.png');
    }

    #[\Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        $html = '';
        if ($value && $value instanceof Tracker_Artifact_ChangesetValue_Integer) {
            $html .= $value->getInteger();
        }
        return $html;
    }

    /**
     * @return string the i18n error message to display if the value submitted by the user is not valid
     */
    #[\Override]
    protected function getValidatorErrorMessage()
    {
        return $this->getLabel() . ' ' . dgettext('tuleap-tracker', 'is not an integer.');
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
    #[\Override]
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $changeset_value = null;
        if ($row = $this->getValueDao()->searchById($value_id, $this->id)->getRow()) {
            $int_row_value = $row['value'];
            if ($int_row_value !== null) {
                $int_row_value = (int) $int_row_value;
            }
            $changeset_value = new Tracker_Artifact_ChangesetValue_Integer($value_id, $changeset, $this, $has_changed, $int_row_value);
        }
        return $changeset_value;
    }

    #[\Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitInteger($this);
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    #[\Override]
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        assert($old_value instanceof Tracker_Artifact_ChangesetValue_Integer);
        return (new ChangesChecker())->hasChanges($old_value, $new_value);
    }

    #[\Override]
    public function isAlwaysInEditMode(): bool
    {
        return false;
    }
}
