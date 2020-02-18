<?php
/**
 * Copyright (c) Enalean 2014-Present. All rights reserved
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

class Docman_View_Download extends Docman_View_View
{

    /* protected */ public function _content($params)
    {
        $url_parameters = ['action' => 'show', 'id' => $params['item']->getId()];
        if (isset($params['version_number'])) {
            $url_parameters['version_number'] = (int) $params['version_number'];
        }
        $redirect_url = DocmanViewURLBuilder::buildActionUrl(
            $params['item'],
            ['default_url' => $this->_controller->getDefaultUrl()],
            $url_parameters,
            true
        );
        $GLOBALS['Response']->redirect($redirect_url);
    }
}
