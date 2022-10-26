<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class CallbackURLSaveTokenIdentifierExtractor
{
    /**
     * @return Ok<ConcealedString>|Err<Fault>
     */
    public function extractSaveTokenIdentifierFromTheCallbackURL(ConcealedString $url): Ok|Err
    {
        $extracted_url_query = parse_url($url->getString(), PHP_URL_QUERY);
        if ($extracted_url_query === false) {
            return Result::err(Fault::fromMessage('Could not parse given callback URL'));
        }

        if ($extracted_url_query === null) {
            return Result::err(Fault::fromMessage('No query parameter in the given callback URL'));
        }

        parse_str($extracted_url_query, $query_parameters);
        sodium_memzero($extracted_url_query);

        if (! isset($query_parameters['token']) || ! is_string($query_parameters['token'])) {
            return Result::err(Fault::fromMessage('Query parameter `token` is missing or malformed in the callback URL'));
        }

        $res = Result::ok(new ConcealedString($query_parameters['token']));
        sodium_memzero($query_parameters['token']);
        return $res;
    }
}
