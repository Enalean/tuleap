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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use Exception;

class UnsupportedDateValueException extends Exception
{
    /**
     * @var ?int
     */
    private $value;

    public function __construct(?int $value, array $known_date_values)
    {
        parent::__construct(sprintf(
            "Unsupported date value ('%s') used in post action. Supported values: '%s'",
            (string) ($value ?: "null"),
            implode("', '", $known_date_values)
        ));
        $this->value = $value;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }
}
