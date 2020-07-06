<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Request;

use HTTPRequest;
use Negotiation\BaseAccept;
use Negotiation\Negotiator;

class HeaderAcceptReader
{
    public static function doesClientPreferHTMLResponse(HTTPRequest $request): bool
    {
        $negotiator    = new Negotiator();
        $accept_header = $request->getFromServer('HTTP_ACCEPT');
        if (! $accept_header || $accept_header === '*/*') {
            return false;
        }

        $priorities = ['text/html'];

        $media_type = $negotiator->getBest($accept_header, $priorities);
        if ($media_type) {
            assert($media_type instanceof BaseAccept);
            return $media_type->getValue() === 'text/html';
        }

        return false;
    }
}
