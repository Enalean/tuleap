<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Taskboard\REST\v1\Card;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_Exception;
use Tracker_FormElement_Field_Numeric;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tracker_REST_Artifact_ArtifactValidator;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class CardPatcher
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Tracker_REST_Artifact_ArtifactUpdater
     */
    private $updater;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        Tracker_REST_Artifact_ArtifactUpdater $updater
    ) {
        $this->form_element_factory = $form_element_factory;

        $this->updater = $updater;
    }

    public static function build(): self
    {
        $form_element_factory = Tracker_FormElementFactory::instance();
        $updater              = new Tracker_REST_Artifact_ArtifactUpdater(
            new Tracker_REST_Artifact_ArtifactValidator(
                $form_element_factory
            )
        );

        return new self($form_element_factory, $updater);
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function patchCard(Artifact $artifact, PFUser $user, CardPatchRepresentation $payload): void
    {
        $remaining_effort_field = $this->getRemainingEffortField($artifact, $user);
        $values                 = $this->getUpdateValues($payload, $remaining_effort_field);

        try {
            $this->updater->update($user, $artifact, $values);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    private function getRemainingEffortField(
        Artifact $artifact,
        PFUser $user
    ): Tracker_FormElement_Field_Numeric {
        $remaining_effort_field = $this->form_element_factory->getNumericFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            \Tracker::REMAINING_EFFORT_FIELD_NAME
        );
        if (! $remaining_effort_field instanceof Tracker_FormElement_Field_Numeric) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-taskboard', "The artifact does not have a remaining effort numeric field")
            );
        }
        if (! $remaining_effort_field->userCanUpdate($user)) {
            throw new RestException(403);
        }

        return $remaining_effort_field;
    }

    /**
     * @return ArtifactValuesRepresentation[]
     */
    private function getUpdateValues(
        CardPatchRepresentation $payload,
        Tracker_FormElement_Field_Numeric $remaining_effort_field
    ): array {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = (int) $remaining_effort_field->getId();
        if ($remaining_effort_field instanceof \Tracker_FormElement_Field_Computed) {
            $representation->manual_value    = $payload->remaining_effort;
            $representation->is_autocomputed = false;
        } else {
            $representation->value = $payload->remaining_effort;
        }

        return [$representation];
    }
}
