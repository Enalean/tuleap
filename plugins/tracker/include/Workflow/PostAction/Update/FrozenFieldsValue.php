<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionVisitor;
use Tuleap\Tracker\Workflow\Update\PostAction;

final class FrozenFieldsValue implements PostAction
{
    /**
     * @var array
     */
    private $field_ids;

    public function __construct(array $field_ids)
    {
        $this->field_ids = $field_ids;
    }

    /**
     * @return array
     */
    public function getFieldIds(): array
    {
        return $this->field_ids;
    }

    public function accept(PostActionVisitor $visitor)
    {
        $visitor->visitFrozenFieldsValue($this);
    }
}
