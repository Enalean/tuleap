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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionVisitor;

final class SetFloatValue implements SetFieldValue
{
    /**
     * @var int $field_id
     */
    private $field_id;

    /**
     * @var float $value
     */
    private $value;

    public function __construct(int $field_id, float $value)
    {
        $this->field_id = $field_id;
        $this->value    = $value;
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function accept(PostActionVisitor $visitor)
    {
        $visitor->visitSetFloatValue($this);
    }
}
