<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Docman\View\DocmanViewURLBuilder;

/* abstract */ class Docman_View_RedirectAfterCrud extends Docman_View_View
{

    public function _content($params)
    {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } elseif (isset($params['default_url_params'])) {
            $url = DocmanViewURLBuilder::buildUrl($params['default_url'], $params['default_url_params'], false);
        } else {
            $url = $params['default_url'];
        }

        if (isset($params['redirect_anchor'])) {
            $url .= $params['redirect_anchor'];
        }

        $GLOBALS['Response']->redirect($url);
    }
}
