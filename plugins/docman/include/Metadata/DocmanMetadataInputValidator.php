<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\Metadata;

class DocmanMetadataInputValidator
{
    /**
     * Convert user input to internal storage form.
     *
     * Warning: Unfortunatly, due to a bad design I don't really now the parm
     * type! Gosh! Well, the only real problem is with list of values because
     * sometime we are dealing with array (input from user) and sometimes with
     * iterators.
     */
    public function validateInput(\Docman_Metadata $metadata, $value)
    {
        switch ($metadata->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                if ($metadata->isMultipleValuesAllowed()) {
                    if (! is_array($value) && ! is_numeric($value)) {
                        //$value = 100; // Set to default
                        // Maybe a warning ?
                    }
                } elseif (is_array($value) && count($value) > 1) {
                    return $value[0]; // If only one value is allowed, the first is taken
                }
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
            case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                break;
            case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                if (preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', $value, $d)) {
                    return mktime(0, 0, 0, (int) $d[2], (int) $d[3], (int) $d[1]);
                } elseif (! preg_match('/\d+/', $value)) { // Allow timestamps as supplied value
                    return 0;
                }
                break;
        }

        return $value;
    }
}
