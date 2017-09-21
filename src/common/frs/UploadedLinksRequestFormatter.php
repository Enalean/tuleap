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
use Valid_FTPURI;
use Valid_HTTPURI;
use Valid_String;

class UploadedLinksRequestFormatter
{
    public function formatFromRequest(HTTPRequest $request)
    {
        if ($request->validArray(new Valid_String('uploaded-link-name'))) {
            $release_links_name = $request->get('uploaded-link-name');
        } else {
            $release_links_name = array();
        }

        if (! $request->validArray(new Valid_HTTPURI('uploaded-link'))
            && ! $request->validArray(new Valid_FTPURI('uploaded-link'))
        ) {
            $release_links = array();
        } else {
            $release_links = $request->get('uploaded-link');
        }

        if (count($release_links_name) != count($release_links)) {
            throw new UploadedLinksInvalidFormException();
        }

        $uploaded_links = array();
        foreach ($release_links as $key => $link) {
            if ($link) {
                $uploaded_links[] = array(
                    "link" => $release_links[$key],
                    "name" => $release_links_name[$key]
                );
            }
        }

        return $uploaded_links;
    }
}
