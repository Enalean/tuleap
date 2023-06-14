<?php
/**
 * Copyright (c) Enalean 2017-Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
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
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_ArtifactId extends Tracker_FormElement_Field_Integer implements Tracker_FormElement_Field_ReadOnly
{
    public $default_properties = [];

    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedFrom::class);
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            return $this->buildMatchExpression("c.artifact_id", $criteria_value);
        }

        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function getQuerySelect(): string
    {
        return "a.id AS " . $this->getQuerySelectName();
    }

    public function getQueryFrom()
    {
        return '';
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby(): string
    {
        return "a.id";
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            (int) $changeset->getArtifact()->getId()
        );
        return $artifact_field_value_full_representation;
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        if ($from_aid != null) {
            return '<a class="direct-link-to-artifact" href="' . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => (int) $value]) . '&from_aid=' . $from_aid . '">' . $value . '</a>';
        }
        return '<a class="direct-link-to-artifact" href="' . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => (int) $value]) . '">' . $value . '</a>';
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions()
    {
        return [];
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
        return $value;
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
        return $this->fetchArtifactValueReadOnly($artifact, $value);
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
        return '<a href="' . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => (int) $artifact->id]) . '">#' . (int) $artifact->id . '</a>';
    }

    /**
     * Fetch artifact value for email
     * @param bool $ignore_perms
     * @param string $format
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
        $output = '';
        switch ($format) {
            case 'html':
                $output .= '<a href="' . \Tuleap\ServerHostname::HTTPSUrl() . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => (int) $artifact->id]) . '">#' . (int) $artifact->id . '</a>';
                break;
            default:
                $output .= '#' . $artifact->id;
                break;
        }
        return $output;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<a href="#' . TRACKER_BASE_URL . '/?aid=123" onclick="return false;">#42</a>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Artifact ID');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the id of the artifact');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/tracker-aid.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/tracker-aid--plus.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html  = '';
        $html .= $artifact->getId();
        return $html;
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return true if Tracler is ok
     */
    public function testImport()
    {
        return true;
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
    {
        //No need to validate artifact id (read only for all)
        return true;
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        return '';
    }

     /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmitMasschange()
    {
        return '';
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitArtifactId($this);
    }
}
