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

class SimilarFieldIdentifier
{
    /** @var string */
    private $field_name;
    /** @var string | null */
    private $bind_name;

    public function __construct($field_name, $bind_name = null)
    {
        $this->field_name = $field_name;
        $this->bind_name  = $bind_name;
    }

    public static function buildFromIdentifierString($identifier_string)
    {
        $parts = explode(SimilarFieldCandidate::SEPARATOR_CHAR, $identifier_string);
        return new SimilarFieldIdentifier($parts[0], $parts[1]);
    }

    public function getLabel()
    {
        return $this->field_name;
    }

    public function getIdentifierWithBindType()
    {
        return $this->field_name . SimilarFieldCandidate::SEPARATOR_CHAR . $this->bind_name;
    }
}
