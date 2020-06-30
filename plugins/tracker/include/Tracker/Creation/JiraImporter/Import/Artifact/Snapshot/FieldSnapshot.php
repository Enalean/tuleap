<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

/**
 * @psalm-immutable
 */
class FieldSnapshot
{
    /**
     * @var FieldMapping
     */
    private $field_mapping;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string|null
     */
    private $rendered_value;

    /**
     * @param mixed $value
     * @param mixed $rendered_value
     */
    public function __construct(FieldMapping $field_mapping, $value, $rendered_value)
    {
        $this->field_mapping  = $field_mapping;
        $this->value          = $value;
        $this->rendered_value = $rendered_value;
    }

    public function getFieldMapping(): FieldMapping
    {
        return $this->field_mapping;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getRenderedValue()
    {
        return $this->rendered_value;
    }
}
