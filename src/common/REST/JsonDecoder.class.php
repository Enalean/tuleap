<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

namespace Tuleap\REST;

use Tuleap\REST\Exceptions\InvalidJsonException;

class JsonDecoder
{

    /**
     * Heuristic to decide whether user submitted a string or tried to upload json
     *
     * Useful when your route accept either string content or json
     *
     * @param string $value
     * @return bool
     */
    public function looksLikeJson($value)
    {
        return substr($value, 0, 1) === '{' && substr($value, -1) === '}';
    }

    /**
     * Convert a json string into a php array
     *
     * @param string $key
     * @param string $value
     *
     * @throws InvalidJsonException (Restler 400)
     *
     * @return array
     */
    public function decodeAsAnArray($key, $value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        $value = json_decode($value, true);
        $this->checkForJsonErrors($key);
        return $value;
    }

    private function checkForJsonErrors($key)
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return;
            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
                throw new InvalidJsonException('parameter "' . $key . '" syntax error, invalid JSON');
            case JSON_ERROR_UTF8:
                throw new InvalidJsonException('Malformed UTF-8 characters, possibly incorrectly encoded parameter "' . $key . '"');
            default:
                throw new InvalidJsonException('Unknown JSON parameter "' . $key . '" error');
        }
    }
}
