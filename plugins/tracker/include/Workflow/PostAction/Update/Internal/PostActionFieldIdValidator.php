<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\Update\SetFieldValue;

class PostActionFieldIdValidator
{
    /**
     * @throws DuplicateFieldIdException
     */
    public function validate(SetFieldValue ...$set_field_values): void
    {
        $field_ids = [];
        foreach ($set_field_values as $set_field_value) {
            $field_ids[] = $set_field_value->getFieldId();
        }
        if ($this->hasDuplicateFieldIds(...$field_ids)) {
            throw new DuplicateFieldIdException();
        }
    }

    private function hasDuplicateFieldIds(int ...$ids): bool
    {
        return count($ids) !== count(array_unique($ids));
    }
}
