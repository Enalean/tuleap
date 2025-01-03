<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

class Docman_View_Move extends Docman_View_Details
{
    public function _getTitle($params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return sprintf(dgettext('tuleap-docman', 'Move %1$s'), $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
    }

    public function _content($params, $view = null, $section = null)
    {
        $token = isset($params['token']) ? $params['token'] : null;
        parent::_content(
            $params,
            new Docman_View_ItemDetailsSectionMove(
                $params['item'],
                $params['default_url'],
                $this->_controller,
                array_merge(
                    ['docman_icons' => $this->_getDocmanIcons($params)],
                    $params
                ),
                $token
            ),
            'actions'
        );
    }
}
