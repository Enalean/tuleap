<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Feedback;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ArtifactLinkValidationContext;

class ArtifactLinkValidator
{
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var NaturePresenterFactory
     */
    private $nature_presenter_factory;
    /**
     * @var ArtifactLinksUsageDao
     */
    private $dao;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        NaturePresenterFactory $nature_presenter_factory,
        ArtifactLinksUsageDao $dao
    ) {
        $this->artifact_factory         = $artifact_factory;
        $this->nature_presenter_factory = $nature_presenter_factory;
        $this->dao                      = $dao;
    }

    /**
     * @param array|null $value
     */
    public function isValid(
        $value,
        \Tuleap\Tracker\Artifact\Artifact $artifact,
        Tracker_FormElement_Field_ArtifactLink $field,
        ArtifactLinkValidationContext $context
    ): bool {
        if ($value === null || $this->isDataSent($value) === false) {
            return true;
        }

        $is_valid   = true;
        $new_values = $value['new_values'];

        if (trim($new_values) != '') {
            $art_id_array = explode(',', $new_values);
            foreach ($art_id_array as $artifact_id) {
                $artifact_id = trim($artifact_id);

                if ($this->isArtifactIdDefined($field, $artifact_id) === false) {
                    $is_valid = false;
                    continue;
                }

                $linked_artifact = $this->getArtifact($field, $artifact_id);
                if ($linked_artifact === null) {
                    $is_valid = false;
                    continue;
                }

                if ($this->isTrackerDeleted($linked_artifact, $field, $artifact_id) === true) {
                    $is_valid = false;
                    continue;
                }

                if ($this->isProjectActive($linked_artifact) === false) {
                    $is_valid = false;
                    continue;
                }
            }
        }

        if ($this->areTypesValid($artifact, $value, $field, $context) === false) {
            $is_valid = false;
        }

        return $is_valid;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isDataSent($value)
    {
        return isset($value['new_values']);
    }

    /**
     * @param $artifact_id
     *
     * @return bool
     */
    private function isArtifactIdDefined(Tracker_FormElement_Field_ArtifactLink $field, $artifact_id)
    {
        $artifact_id = trim($artifact_id);
        if ($artifact_id === "") {
            $GLOBALS['Response']->addFeedback(
                'error',
                sprintf(dgettext('tuleap-tracker', '%1$s: %2$s is not an artifact id.'), $field->getLabel(), $artifact_id)
            );

            return false;
        }

        return true;
    }

    /**
     * @param $artifact_id
     *
     * @return \Tuleap\Tracker\Artifact\Artifact|null
     */
    private function getArtifact(Tracker_FormElement_Field_ArtifactLink $field, $artifact_id)
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null) {
            $GLOBALS['Response']->addFeedback(
                'error',
                sprintf(dgettext('tuleap-tracker', '%1$s: %2$s is not an artifact id.'), $field->getLabel(), $artifact_id)
            );

            return null;
        }

        return $artifact;
    }

    /**
     * @param $artifact_id
     *
     * @return bool
     */
    private function isTrackerDeleted(
        Artifact $artifact,
        Tracker_FormElement_Field_ArtifactLink $field,
        $artifact_id
    ) {
        if ($artifact->getTracker()->isDeleted()) {
            $GLOBALS['Response']->addFeedback(
                'error',
                sprintf(dgettext('tuleap-tracker', '%1$s : artifact #%2$s does not exist.'), $field->getLabel(), $artifact_id)
            );

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isProjectActive(Artifact $artifact)
    {
        if (! $artifact->getTracker()->getProject()->isActive()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-tracker', 'The artifact #%d is not located in an active project.'),
                    $artifact->getId()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param array $value
     */
    private function areTypesValid(
        Artifact $artifact,
        array $value,
        Tracker_FormElement_Field_ArtifactLink $field,
        ArtifactLinkValidationContext $context
    ): bool {
        if ($artifact->getTracker()->isProjectAllowedToUseNature() === false || ! isset($value['natures'])) {
            return true;
        }

        $project                   = $artifact->getTracker()->getProject();
        $editable_link_types       = $this->getEditableLinkShortnames($project);
        $used_types_by_artifact_id = $this->getUsedTypeShortnameByArtifactID($artifact, $field);

        foreach ($value['natures'] as $artifact_id => $nature_shortname) {
            $nature = $this->nature_presenter_factory->getFromShortname($nature_shortname);
            if (! $nature) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    sprintf(dgettext('tuleap-tracker', 'Type is missing for artifact #%1$s.'), $artifact->getId())
                );

                return false;
            }

            if ($this->dao->isTypeDisabledInProject($artifact->getTracker()->getProject()->getID(), $nature_shortname)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            'The artifact link type "%s" is disabled and cannot be used to link artifact #%s'
                        ),
                        $nature_shortname,
                        $artifact_id
                    )
                );

                return false;
            }

            $is_an_editable_link_type      = $nature_shortname === '' || isset($editable_link_types[$nature_shortname]);
            $is_an_unchanged_existing_link = isset($used_types_by_artifact_id[(int) $artifact_id]) && $used_types_by_artifact_id[(int) $artifact_id] === $nature_shortname;
            if (! $context->isSystemAction() && ! $is_an_editable_link_type && ! $is_an_unchanged_existing_link) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            'The artifact link type "%s" cannot be used to link artifact #%s manually'
                        ),
                        $nature_shortname,
                        $artifact_id
                    )
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, true>
     */
    private function getEditableLinkShortnames(\Project $project): array
    {
        $editable_link_types = $this->nature_presenter_factory->getAllTypesEditableInProject($project);

        $editable_link_types_shortnames = [];

        foreach ($editable_link_types as $editable_link_type) {
            $editable_link_types_shortnames[$editable_link_type->shortname] = true;
        }

        return $editable_link_types_shortnames;
    }

    /**
     * @return array<int,string>
     */
    private function getUsedTypeShortnameByArtifactID(Artifact $artifact, Tracker_FormElement_Field_ArtifactLink $field): array
    {
        $changeset = $artifact->getLastChangesetWithFieldValue($field);
        if ($changeset === null) {
            return [];
        }
        $changeset_value = $changeset->getValue($field);
        if ($changeset_value === null) {
            return [];
        }

        $all_art_links = $changeset_value->getValue();

        $used_types_by_artifact_id = [];

        foreach ($all_art_links as $artifact_id => $art_link_info) {
            $used_types_by_artifact_id[(int) $artifact_id] = $art_link_info->getNature();
        }

        return $used_types_by_artifact_id;
    }
}
