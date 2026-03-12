<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Override;
use PFUser;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\FormElement\RetrieveFormElementByName;
use Tuleap\Tracker\FormElement\TrackerFormElement;

final readonly class BasicPropertiesHandler implements PatchHandler
{
    public function __construct(private FieldDao $dao, private RetrieveFormElementByName $factory)
    {
    }

    #[Override]
    public function handle(TrackerFormElement $field, TrackerFieldPatchRepresentation $patch, PFUser $current_user): void
    {
        $original_name        = $field->name;
        $original_label       = $field->label;
        $original_description = $field->description;

        $this->setNewName($field, $patch);
        $this->setNewLabel($field, $patch);
        $this->setNewDescription($field, $patch);

        if ($original_label !== $field->label || $original_description !== $field->description || $original_name !== $field->name) {
            $this->dao->save($field);
        }
    }

    private function setNewLabel(TrackerFormElement $field, TrackerFieldPatchRepresentation $patch): void
    {
        if ($patch->label !== null) {
            $label = trim($patch->label);
            if ($label === '') {
                throw new I18NRestException(400, dgettext('tuleap-tracker', 'Label cannot be empty'));
            }
            $field->label = $label;
        }
    }

    private function setNewDescription(TrackerFormElement $field, TrackerFieldPatchRepresentation $patch): void
    {
        if ($patch->description !== null) {
            $field->description = trim($patch->description);
        }
    }

    private function setNewName(TrackerFormElement $field, TrackerFieldPatchRepresentation $patch): void
    {
        if ($patch->name !== null) {
            $name = FieldNameFormatter::getFormattedName($patch->name);
            if ($name === '') {
                throw new I18NRestException(400, dgettext('tuleap-tracker', 'Name cannot be empty'));
            }

            $existing_field = $this->factory->getFormElementByName($field->getTrackerId(), $name);
            if ($existing_field !== null && $existing_field->getId() !== $field->getId()) {
                throw new I18NRestException(400, dgettext('tuleap-tracker', 'Unable to change the name of the element, it is already in use by another one'));
            }

            $field->name = $name;
        }
    }
}
