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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Integer\ChangesChecker;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerFieldDao;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerValueDao;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_Integer extends Tracker_FormElement_Field_Numeric
{
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
                                AND " . $match_expression->sql . "
                             ) ",
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

    public function fetchCriteriaValue($criteria)
    {
        $html           = '<input type="text" name="criteria[' . $this->id . ']" id="tracker_report_criteria_' . $this->id . '" value="';
        $criteria_value = $this->getCriteriaValue($criteria);

        if ($criteria_value !== '') {
            $html_purifier = Codendi_HTMLPurifier::instance();
            $html         .= $html_purifier->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }

        $html .= '" />';
        return $html;
    }

    public function getQueryFrom()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;

        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN tracker_changeset_value_int AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->id . " )";
    }

    protected function buildMatchExpression(string $field_name, $criteria_value): Option
    {
        return parent::buildMatchExpression($field_name, $criteria_value);
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Int_ValueDao();
    }

    public function canBeUsedToSortReport()
    {
        return true;
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        return (string) $value;
    }

    protected function getValueDao()
    {
        return new IntegerValueDao();
    }

    protected function getDao()
    {
        return new IntegerFieldDao();
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Integer');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A textfield wich accept only integers');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field-int.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field-int--plus.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if ($value) {
            $html .= $value->getInteger();
        }
        return $html;
    }

    /**
     * @return string the i18n error message to display if the value submitted by the user is not valid
     */
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

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitInteger($this);
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        assert($old_value instanceof Tracker_Artifact_ChangesetValue_Integer);
        return (new ChangesChecker())->hasChanges($old_value, $new_value);
    }
}
