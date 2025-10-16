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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ValidateArtifactLinkValueEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ArtifactLinkValidationContext;

class ArtifactLinkValidator
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private TypePresenterFactory $type_presenter_factory,
        private ArtifactLinksUsageDao $dao,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    /**
     * @param array|null $value
     */
    public function isValid(
        $value,
        \Tuleap\Tracker\Artifact\Artifact $artifact,
        ArtifactLinkField $field,
        ArtifactLinkValidationContext $context,
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

        if (is_array($value)) {
            $event = $this->event_dispatcher->dispatch(
                ValidateArtifactLinkValueEvent::buildFromSubmittedValues($artifact, $value),
            );

            if ($event->isValid() === false) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $event->getErrorMessage(),
                );

                $is_valid = false;
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

    private function isArtifactIdDefined(ArtifactLinkField $field, string $artifact_id): bool
    {
        $artifact_id = trim($artifact_id);
        if ($artifact_id === '') {
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
    private function getArtifact(ArtifactLinkField $field, $artifact_id)
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
        ArtifactLinkField $field,
        $artifact_id,
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

    private function areTypesValid(
        Artifact $artifact,
        array $value,
        ArtifactLinkField $field,
        ArtifactLinkValidationContext $context,
    ): bool {
        if ($artifact->getTracker()->isProjectAllowedToUseType() === false || ! isset($value['types'])) {
            return true;
        }

        $project                   = $artifact->getTracker()->getProject();
        $editable_link_types       = $this->getEditableLinkShortnames($project);
        $used_types_by_artifact_id = $this->getUsedTypeShortnameByArtifactID($artifact, $field);

        foreach ($value['types'] as $artifact_id => $type_shortname) {
            $type = $this->type_presenter_factory->getFromShortname($type_shortname);
            if (! $type) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    sprintf(dgettext('tuleap-tracker', 'Type is missing for artifact #%1$s.'), $artifact_id)
                );

                return false;
            }

            if ($this->dao->isTypeDisabledInProject((int) $artifact->getTracker()->getProject()->getID(), $type_shortname)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            'The artifact link type "%s" is disabled and cannot be used to link artifact #%s'
                        ),
                        $type_shortname,
                        $artifact_id
                    )
                );

                return false;
            }

            $is_an_editable_link_type      = $type_shortname === '' || isset($editable_link_types[$type_shortname]);
            $is_an_unchanged_existing_link = isset($used_types_by_artifact_id[(int) $artifact_id]) && $used_types_by_artifact_id[(int) $artifact_id] === $type_shortname;
            if (! $context->isSystemAction() && ! $is_an_editable_link_type && ! $is_an_unchanged_existing_link) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            'The artifact link type "%s" cannot be used to link artifact #%s manually'
                        ),
                        $type_shortname,
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
        $editable_link_types = $this->type_presenter_factory->getAllTypesEditableInProject($project);

        $editable_link_types_shortnames = [];

        foreach ($editable_link_types as $editable_link_type) {
            $editable_link_types_shortnames[$editable_link_type->shortname] = true;
        }

        return $editable_link_types_shortnames;
    }

    /**
     * @return array<int,string>
     */
    private function getUsedTypeShortnameByArtifactID(Artifact $artifact, ArtifactLinkField $field): array
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
            $used_types_by_artifact_id[(int) $artifact_id] = $art_link_info->getType();
        }

        return $used_types_by_artifact_id;
    }
}
