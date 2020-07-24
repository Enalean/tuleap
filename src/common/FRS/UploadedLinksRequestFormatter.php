<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *   This file is a part of Tuleap.
 *
 *   Tuleap is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   Tuleap is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\FRS;

use HTTPRequest;
use Rule_Regexp;
use Valid_FTPURI;
use Valid_LocalURI;
use Valid_String;

class UploadedLinksRequestFormatter
{
    public function formatFromRequest(HTTPRequest $request)
    {
        if ($request->validArray(new Valid_String('uploaded-link-name'))) {
            $release_links_name = $request->get('uploaded-link-name');
        } else {
            $release_links_name = [];
        }

        $uploaded_links = [];
        $valid_http     = new Rule_Regexp(Valid_LocalURI::URI_REGEXP);
        $valid_ftp      = new Rule_Regexp(Valid_FTPURI::URI_REGEXP);

        if ($request->get('uploaded-link')) {
            foreach ($request->get('uploaded-link') as $key => $link) {
                if (! $valid_ftp->isValid($link) && ! $valid_http->isValid($link)) {
                    throw new UploadedLinksInvalidFormException();
                }

                if (! isset($release_links_name[$key])) {
                    throw new UploadedLinksInvalidFormException();
                }

                $uploaded_links[] = [
                    "link" => $link,
                    "name" => $release_links_name[$key]
                ];
            }
        }
        return $uploaded_links;
    }
}
