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

class URISanitizerTest extends \TuleapTestCase
{
    public function itDoesNotTouchValidURI()
    {
        $validator_local_uri = mock('Valid_LocalURI');
        stub($validator_local_uri)->validate()->returns(true);

        $uri_sanitizer = new URISanitizer($validator_local_uri);

        $uri = '/valid_uri';

        $this->assertEqual($uri_sanitizer->sanitizeForHTMLAttribute($uri), $uri);
    }

    public function itManglesInvalidURI()
    {
        $validator_local_uri = mock('Valid_LocalURI');
        stub($validator_local_uri)->validate()->returns(false);

        $uri_sanitizer = new URISanitizer($validator_local_uri);

        $uri = 'invalid_uri';

        $this->assertEqual($uri_sanitizer->sanitizeForHTMLAttribute($uri), '');
    }
}
