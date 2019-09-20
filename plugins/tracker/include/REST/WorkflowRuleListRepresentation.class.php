<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tuleap\REST\JsonCast;

class WorkflowRuleListRepresentation
{

    /**
     * @var int
     */
    public $source_field_id;

    /**
     * @var int
     */
    public $source_value_id;

    /**
     * @var int
     */
    public $target_field_id;

    /**
     * @var int
     */
    public $target_value_id;

    public function build($source_field_id, $source_value_id, $target_field_id, $target_value_id)
    {
        $this->source_field_id = JsonCast::toInt($source_field_id);
        $this->source_value_id = JsonCast::toInt($source_value_id);
        $this->target_field_id = JsonCast::toInt($target_field_id);
        $this->target_value_id = JsonCast::toInt($target_value_id);
    }
}
