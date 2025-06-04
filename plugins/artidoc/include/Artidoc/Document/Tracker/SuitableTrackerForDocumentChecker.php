<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Tracker;

use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Semantic\Description\TrackerSemanticDescription;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

final class SuitableTrackerForDocumentChecker implements CheckTrackerIsSuitableForDocument
{
    public function __construct(
        private RetrieveUsedFields $form_element_factory,
    ) {
    }

    public function checkTrackerIsSuitableForDocument(\Tracker $tracker, Artidoc $document, \PFUser $user): Ok|Err
    {
        if ($tracker->isDeleted()) {
            return Result::err(TrackerNotFoundFault::forDocument($document));
        }

        if (! $tracker->userCanView($user)) {
            return Result::err(TrackerNotFoundFault::forDocument($document));
        }

        $title_field = TrackerSemanticTitle::load($tracker)->getField();
        if (! $title_field) {
            return Result::err(NoSemanticTitleFault::forDocument($document));
        }
        if (! ($title_field instanceof \Tracker_FormElement_Field_String)) {
            return Result::err(SemanticTitleIsNotAStringFault::forDocument($document));
        }

        $description_field = TrackerSemanticDescription::load($tracker)->getField();
        if (! $description_field) {
            return Result::err(NoSemanticDescriptionFault::forDocument($document));
        }

        if (! $this->areTitleAndDescriptionFieldTheOnlyRequiredFields($tracker, $title_field, $description_field)) {
            return Result::err(TooManyRequiredFieldsFault::forDocument($document));
        }

        return Result::ok($tracker);
    }

    private function areTitleAndDescriptionFieldTheOnlyRequiredFields(
        \Tracker $tracker,
        \Tracker_FormElement_Field $field_title,
        \Tracker_FormElement_Field $description_field,
    ): bool {
        $title_field_id       = $field_title->getId();
        $description_field_id = $description_field->getId();
        $tracker_fields       = $this->form_element_factory->getUsedFields($tracker);
        foreach ($tracker_fields as $field) {
            \assert($field instanceof \Tracker_FormElement_Field);
            if ($field->getId() === $title_field_id) {
                continue;
            }
            if ($field->getId() === $description_field_id) {
                continue;
            }
            if ($field->isRequired()) {
                return false;
            }
        }

        return true;
    }
}
