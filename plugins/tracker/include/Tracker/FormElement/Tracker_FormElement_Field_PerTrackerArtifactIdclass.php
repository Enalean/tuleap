<?php
/**
 * Copyright (c) Enalean 2017-Present. All rights reserved
 * Copyright (c) Tuleap, 2013. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2013. Jtekt Europe.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;

class Tracker_FormElement_Field_PerTrackerArtifactId extends Tracker_FormElement_Field_ArtifactId
{
    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            return $this->buildMatchExpression("artifact.per_tracker_artifact_id", $criteria_value);
        }
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function getQuerySelect(): string
    {
        return "a.per_tracker_artifact_id AS " . $this->getQuerySelectName();
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby(): string
    {
        return "a.per_tracker_artifact_id";
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        return '<a class="direct-link-to-artifact" href="' . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => $artifact_id]) . '">' . $value . '</a>';
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            (int) $changeset->getArtifact()->getPerTrackerArtifactId()
        );
        return $artifact_field_value_full_representation;
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
        return '<a href="' . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => (int) $artifact->id]) . '">' . (int) $artifact->getPerTrackerArtifactId() . '</a>';
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
                $output .= '<a href="' . \Tuleap\ServerHostname::HTTPSUrl() . TRACKER_BASE_URL . '/?' . http_build_query(['aid' => (int) $artifact->id]) . '">' . $artifact->getPerTrackerArtifactId() . '</a>';
                break;
            default:
                $output .= $artifact->getPerTrackerArtifactId();
                break;
        }
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<a href="#' . TRACKER_BASE_URL . '/?aid=123" onclick="return false;">3</a>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Per tracker id');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the in-tracker numerotation');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-perTrackerId.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-perTrackerId--plus.png');
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
        $html .= $artifact->getPerTrackerArtifactId();
        return $html;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitPerTrackerArtifactId($this);
    }

    public function isCSVImportable(): bool
    {
        return false;
    }
}
