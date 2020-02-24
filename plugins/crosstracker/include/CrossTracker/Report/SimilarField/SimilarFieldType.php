<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\SimilarField;

class SimilarFieldType
{
    /** @var string */
    private $field_type;
    /** @var string | null */
    private $bind_name;

    public function __construct(
        $field_type,
        $bind_name = null
    ) {
        $this->field_type = $field_type;
        $this->bind_name  = $bind_name;
    }

    public function getTypeIdentifierString()
    {
        if (! $this->bind_name) {
            return $this->field_type;
        }

        return $this->field_type . SimilarFieldCandidate::SEPARATOR_CHAR . $this->bind_name;
    }
}
