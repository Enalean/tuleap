<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\Semantic\Timeframe\ArtifactTimeframeHelper;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Tracker_FormElement_Field_Numeric extends Tracker_FormElement_Field_Alphanum implements Tracker_FormElement_IComputeValues
{
    public $default_properties = [
        'maxchars'      => [
            'value' => 0,
            'type'  => 'string',
            'size'  => 3,
        ],
        'size'          => [
            'value' => 5,
            'type'  => 'string',
            'size'  => 3,
        ],
        'default_value' => [
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ],
    ];

    public function getComputedValue(
        PFUser $user,
        Artifact $artifact,
        $timestamp = null,
    ) {
        if ($this->userCanRead($user)) {
            if ($timestamp !== null) {
                return $this->getComputedValueAt($artifact, $timestamp);
            } else {
                return $this->getCurrentComputedValue($artifact);
            }
        }
    }

    /**
     * @param int              $timestamp
     *
     * @return mixed
     */
    private function getComputedValueAt(Artifact $artifact, $timestamp)
    {
        $row = $this->getValueDao()->getValueAt($artifact->getId(), $this->getId(), $timestamp);
        return $row['value'];
    }

    /**
     * @return mixed
     */
    private function getCurrentComputedValue(Artifact $artifact)
    {
        $row = $this->getValueDao()->getLastValue($artifact->getId(), $this->getId());
        if ($row) {
            return $row['value'];
        }
        return 0;
    }

    public function getQuerySelect(): string
    {
        $R2 = 'R2_' . $this->id;
        return "$R2.value AS " . $this->getQuerySelectName();
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
        $R1       = 'R1_' . $this->id;
        $R2       = 'R2_' . $this->id;
        $same     = [];
        $separate = [];
        foreach ($functions as $f) {
            if (in_array($f, $this->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = [
                        'function' => $f,
                        'select'   => "$R2.value AS label, count(*) AS value",
                        'group_by' => "$R2.value",
                    ];
                } else {
                    $same[] = "$f($R2.value) AS `" . $this->name . "_$f`";
                }
            }
        }
        return [
            'same_query'       => implode(', ', $same),
            'separate_queries' => $separate,
        ];
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions()
    {
        return ['AVG', 'COUNT', 'COUNT_GRBY', 'MAX', 'MIN', 'STD', 'SUM'];
    }

    protected function buildMatchExpression(string $field_name, $criteria_value): Option
    {
        $expr = parent::buildMatchExpression($field_name, $criteria_value);
        if ($expr->isNothing()) {
            $matches = [];
            if (preg_match("/^(<|>|>=|<=)\s*($this->pattern)$/", $criteria_value, $matches)) {
                // It's < or >,  = and a number then use as is
                $value = $this->cast($matches[2]);
                $expr  = Option::fromValue(
                    new ParametrizedSQLFragment(
                        $field_name . ' ' . $matches[1] . ' ?',
                        [$value]
                    )
                );
            } elseif (preg_match("/^($this->pattern)$/", $criteria_value, $matches)) {
                // It's a number so use  equality
                $value = $this->cast($matches[1]);
                $expr  = Option::fromValue(
                    new ParametrizedSQLFragment(
                        $field_name . ' = ?',
                        [$value]
                    )
                );
            } elseif (preg_match("/^($this->pattern)\s*-\s*($this->pattern)$/", $criteria_value, $matches)) {
                // it's a range number1-number2
                $min  = $this->cast($matches[1]);
                $max  = $this->cast($matches[2]);
                $expr = Option::fromValue(
                    new ParametrizedSQLFragment(
                        $field_name . ' >= ? AND ' . $field_name . ' <= ?',
                        [$min, $max],
                    )
                );
            }
        }
        return $expr;
    }

    protected $pattern = '[+\-]*[0-9]+';
    protected function cast($value)
    {
        return (int) $value;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $html  = '';
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text"
                         data-test="' . $hp->purify($this->getName()) . '"
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         name="artifact[' . $this->id . ']"
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" />';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        $html  = '';
        $value = dgettext('tuleap-tracker', 'Unchanged');
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text"
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         name="artifact[' . $this->id . ']"
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" />';
        return $html;
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
        $html = '';
        if (isset($submitted_values[$this->getId()])) {
            $value = $submitted_values[$this->getId()];
        } else {
            if ($value != null) {
                $value = $value->getValue();
            }
        }
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text"
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         name="artifact[' . $this->id . ']"
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" />';
        return $html;
    }

    /**
     * Fetch the field value in artifact to be displayed in mail
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     * @param string                          $format   mail format
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
        if (empty($value) || ! $value->getNumeric()) {
            return '-';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $value  = $value->getNumeric();
                $output = $value;
                break;
        }
        return $output;
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
        if ($value === null) {
            return $this->getNoValueLabel();
        }

        $numeric_value = $value->getValue();
        if ($numeric_value === null) {
            return $this->getNoValueLabel();
        }

        $hp = Codendi_HTMLPurifier::instance();

        $html_value = $hp->purify($numeric_value, CODENDI_PURIFIER_CONVERT_HTML);

        $user              = $this->getCurrentUser();
        $time_frame_helper = $this->getArtifactTimeframeHelper();

        if ($time_frame_helper->artifactHelpShouldBeShownToUser($user, $this)) {
            $html_value = $html_value . '<span class="artifact-timeframe-helper"> (' . $time_frame_helper->getEndDateArtifactHelperForReadOnlyView($user, $artifact) . ')</span>';
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
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return $old_value->getNumeric() !== $new_value;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input type="text"
                         size="' . $this->getProperty('size') . '"
                         ' . ($this->getProperty('maxchars') ? 'maxlength="' . $this->getProperty('maxchars') . '"' : '')  . '
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  . '" autocomplete="off" />';
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
        return $this->validateValue($value);
    }

    /**
     * Validate a value
     *
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function validateValue($value)
    {
        if ($value !== null && ! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $GLOBALS['Response']->addFeedback('error', $this->getValidatorErrorMessage());
            return false;
        }
        if ($value && ! preg_match('/^' . $this->pattern . '$/', $value)) {
            $GLOBALS['Response']->addFeedback('error', $this->getValidatorErrorMessage());
            return false;
        }
        return true;
    }

    /**
     * @return string the i18n error message to display if the value submitted by the user is not valid
     */
    abstract protected function getValidatorErrorMessage();

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return bool true if Tracler is ok
     */
    public function testImport()
    {
        if (parent::testImport()) {
            if (! ($this->default_properties['maxchars'] && $this->default_properties['size'])) {
                var_dump($this, 'Properties must be "maxchars" and "size"');
                return false;
            }
        }
        return true;
    }

    public function getCachedValue(PFUser $user, Artifact $artifact, $timestamp = null)
    {
        return $this->getComputedValue($user, $artifact, $timestamp);
    }

    protected function getArtifactTimeframeHelper(): ArtifactTimeframeHelper
    {
        return new ArtifactTimeframeHelper(
            SemanticTimeframeBuilder::build(),
            \BackendLogger::getDefaultLogger()
        );
    }
}
