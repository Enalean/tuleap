<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference;

use Controler;
use HTTPRequest;

class ReferenceAdministration extends Controler
{

    public function request()
    {
        $request = HTTPRequest::instance();

        session_require(array('group' => $request->get('group_id'),'admin_flags' => 'A'));

        if ($request->exist('view')) {
            switch ($request->get('view')) {
                case 'creation':
                    $this->view = 'creation';
                    break;
                case 'edit':
                    $this->view = 'edit';
                    break;
                default:
                    $this->view = 'browse';
                    break;
            }
        } else {
            $this->view = 'browse';
        }

        if ($request->exist('action')) {
            switch ($request->get('action')) {
                case 'do_edit':
                    $this->action = 'do_edit';
                    break;
                case 'do_create':
                    $this->action = 'do_create';
                    break;
                case 'do_delete':
                    $this->action = 'do_delete';
                    break;
                default:
                    break;
            }
        }
    }
}
