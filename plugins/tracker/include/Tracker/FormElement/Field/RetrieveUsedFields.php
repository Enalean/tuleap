<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field;

use Tracker_FormElement_Field;

interface RetrieveUsedFields
{
    /**
     * All fields used by the tracker
     * @return \Tracker_FormElement_Field[]
     */
    public function getUsedFields(\Tracker $tracker): array;

    public function getUsedFormElementFieldById(int $id): ?Tracker_FormElement_Field;

    public function getUsedFieldByName(int $tracker_id, string $field_name): ?Tracker_FormElement_Field;
}
