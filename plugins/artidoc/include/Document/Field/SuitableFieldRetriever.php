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
use Tracker_FormElement_Field_List_Bind_Null;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsDescriptionSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsTitleSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotFoundFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotSupportedFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\TestManagement\Step\Execution\Field\StepsExecution;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\LastUpdateBy\LastUpdateByField;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\NumericField;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;

final readonly class SuitableFieldRetriever
{
    public function __construct(
        private RetrieveUsedFields $factory,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
        private RetrieveSemanticTitleField $retrieve_title_field,
    ) {
    }

    /**
     * @return Ok<TextField> | Ok<ListField> | Ok<ArtifactLinkField> | Ok<NumericField> | OK<DateField> | Ok<PermissionsOnArtifactField> | Ok<StepsDefinition> | Ok<StepsExecution> | Err<Fault>
     */
    public function retrieveField(int $field_id, PFUser $user): Ok|Err
    {
        $field = $this->factory->getUsedFormElementFieldById($field_id);

        if ($field === null || ! $field->userCanRead($user)) {
            return Result::err(FieldNotFoundFault::build($field_id));
        }

        return match (true) {
            $field instanceof TextField                  => $this->validateTextField($field),
            $field instanceof ListField                  => $this->validateListField($field),
            $field instanceof ArtifactLinkField          => Result::ok($field),
            $field instanceof NumericField               => Result::ok($field),
            $field instanceof DateField                  => Result::ok($field),
            $field instanceof PermissionsOnArtifactField => Result::ok($field),
            $field instanceof StepsDefinition            => Result::ok($field),
            $field instanceof StepsExecution             => Result::ok($field),
            default                                      => Result::err(FieldNotSupportedFault::build($field_id))
        };
    }

    /**
     * @return Ok<TextField>|Err<Fault>
     */
    private function validateTextField(
        TextField $field,
    ): Ok|Err {
        $field_id = $field->getId();
        $tracker  = $field->getTracker();

        $semantic_title_field = $this->retrieve_title_field->fromTracker($tracker);
        if ($semantic_title_field && $semantic_title_field->getId() === $field_id) {
            return Result::err(FieldIsTitleSemanticFault::build($field_id));
        }

        $semantic_description_field = $this->retrieve_description_field->fromTracker($tracker);
        if ($semantic_description_field && $semantic_description_field->getId() === $field_id) {
            return Result::err(FieldIsDescriptionSemanticFault::build($field_id));
        }
        return Result::ok($field);
    }

    /**
     * @return Ok<ListField>|Err<Fault>
     */
    private function validateListField(ListField $field): Ok|Err
    {
        if (
            $field instanceof LastUpdateByField
            || $field instanceof SubmittedByField
        ) {
            /** @psalm-var ListField $field_return */
            $field_return = $field;
            return Result::ok($field_return);
        }

        $bind_type = $field->getBind()?->getType();

        if ($bind_type !== Tracker_FormElement_Field_List_Bind_Null::TYPE && $bind_type !== null) {
            return Result::ok($field);
        }

        return Result::err(FieldNotSupportedFault::build($field->getId()));
    }
}
