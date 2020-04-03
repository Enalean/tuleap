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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Links;

use Luracast\Restler\RestException;
use Rule_Regexp;
use Valid_FTPURI;
use Valid_LocalURI;

class DocmanLinksValidityChecker
{
    /**
     * @throws RestException
     */
    public function checkLinkValidity(string $link_url): void
    {
        $valid_http = new Rule_Regexp(Valid_LocalURI::URI_REGEXP);
        $valid_ftp  = new Rule_Regexp(Valid_FTPURI::URI_REGEXP);
        if (!$valid_ftp->isValid($link_url) && !$valid_http->isValid($link_url)) {
            throw new RestException(
                400,
                sprintf('The link is not a valid URL')
            );
        }
    }
}
