<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tracker_Artifact;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;

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

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        NaturePresenterFactory $nature_presenter_factory
    ) {
        $this->artifact_factory         = $artifact_factory;
        $this->nature_presenter_factory = $nature_presenter_factory;
    }

    public function isValid($value, \Tracker_Artifact $artifact, Tracker_FormElement_Field_ArtifactLink $field)
    {
        if ($this->isDataSent($value) === false) {
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
                if ($this->getArtifact($field, $artifact_id) === null) {
                    $is_valid = false;
                    continue;
                }

                if ($this->isTrackerDeleted($linked_artifact, $field, $artifact_id) === true) {
                    $is_valid = false;
                    continue;
                }
            }
        }

        if ($this->areNaturesValid($artifact, $value) === false) {
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
     * @param Tracker_FormElement_Field_ArtifactLink $field
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
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_artifactlink_value',
                    array($field->getLabel(), $artifact_id)
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param Tracker_FormElement_Field_ArtifactLink $field
     * @param $artifact_id
     *
     * @return \Tracker_Artifact|null
     */
    private function getArtifact(Tracker_FormElement_Field_ArtifactLink $field, $artifact_id)
    {
        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_artifactlink_value',
                    array($field->getLabel(), $artifact_id)
                )
            );

            return null;
        }

        return $artifact;
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param Tracker_FormElement_Field_ArtifactLink $field
     * @param $artifact_id
     *
     * @return bool
     */
    private function isTrackerDeleted(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field_ArtifactLink $field,
        $artifact_id
    ) {
        if ($artifact->getTracker()->isDeleted()) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText(
                    'plugin_tracker_common_artifact',
                    'error_artifactlink_value_not_exist',
                    array($field->getLabel(), $artifact_id)
                )
            );

            return true;
        }

        return false;
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param array $value
     *
     * @return bool
     */
    private function areNaturesValid(Tracker_Artifact $artifact, array $value)
    {
        if ($artifact->getTracker()->isProjectAllowedToUseNature() === true && isset($value['natures'])) {
            foreach ($value['natures'] as $nature_shortname) {
                $nature = $this->nature_presenter_factory->getFromShortname($nature_shortname);
                if (! $nature) {
                    $GLOBALS['Response']->addFeedback(
                        'error',
                        $GLOBALS['Language']->getText(
                            'plugin_tracker_common_artifact',
                            'error_artifactlink_nature_missing',
                            array($artifact->getId())
                        )
                    );

                    return false;
                }
            }
        }

        return true;
    }
}
