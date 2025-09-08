<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTrackerFromField;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class TrackerFromFieldRetriever implements RetrieveTrackerFromField
{
    public function __construct(private \Tracker_FormElementFactory $form_element_factory)
    {
    }

    #[\Override]
    public function fromFieldId(int $field_id): TrackerReference
    {
        $full_field = $this->form_element_factory->getFieldById($field_id);

        if (! $full_field) {
            throw new FieldNotFoundException($field_id);
        }

        return TrackerReferenceProxy::fromTracker($full_field->getTracker());
    }
}
