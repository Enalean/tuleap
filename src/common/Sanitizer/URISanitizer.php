<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Sanitizer;

use Valid_String;

class URISanitizer
{
    /**
     * @var Valid_String[]
     */
    private $validators;

    /**
     * @psalm-param \Valid_HTTPURI|\Valid_HTTPSURI|\Valid_LocalURI|\Valid_FTPURI ...$validators
     */
    public function __construct(Valid_String ...$validators)
    {
        $this->validators = $validators;
    }

    public function sanitizeForHTMLAttribute(string $uri): string
    {
        $is_valid = array_reduce(
            $this->validators,
            static function (bool $is_valid, Valid_String $validator) use ($uri) {
                return $is_valid || $validator->validate($uri);
            },
            false
        );

        return $is_valid ? $uri : '';
    }
}
