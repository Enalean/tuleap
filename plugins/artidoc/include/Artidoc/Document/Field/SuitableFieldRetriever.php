<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use PFUser;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_String;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsDescriptionSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsTitleSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotFoundFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotSupportedFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Semantic\Description\TrackerSemanticDescription;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

final readonly class SuitableFieldRetriever
{
    public function __construct(private RetrieveUsedFields $factory)
    {
    }

    /**
     * @return Ok<Tracker_FormElement_Field_String> | Ok<Tracker_FormElement_Field_List> | Err<Fault>
     */
    public function retrieveField(int $field_id, PFUser $user): Ok|Err
    {
        $field = $this->factory->getUsedFormElementFieldById($field_id);

        if ($field === null || ! $field->userCanRead($user)) {
            return Result::err(FieldNotFoundFault::build($field_id));
        }

        return match (true) {
            $field instanceof Tracker_FormElement_Field_String => $this->validateStringField($field),
            $field instanceof Tracker_FormElement_Field_List
            && $field->getBind()->getType() === Tracker_FormElement_Field_List_Bind_Ugroups::TYPE => Result::ok($field),
            default => Result::err(FieldNotSupportedFault::build($field_id))
        };
    }

    /**
     * @return Ok<Tracker_FormElement_Field_String>|Err<Fault>
     */
    private function validateStringField(
        Tracker_FormElement_Field_String $field,
    ): Ok|Err {
        $field_id = $field->getId();
        $tracker  = $field->getTracker();

        $semantic_title_field = TrackerSemanticTitle::load($tracker)->getField();
        if ($semantic_title_field && $semantic_title_field->getId() === $field_id) {
            return Result::err(FieldIsTitleSemanticFault::build($field_id));
        }

        $semantic_description_field = TrackerSemanticDescription::load($tracker)->getField();
        if ($semantic_description_field && $semantic_description_field->getId() === $field_id) {
            return Result::err(FieldIsDescriptionSemanticFault::build($field_id));
        }
        return Result::ok($field);
    }
}
