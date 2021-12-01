<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Label;

use HTTPRequest;

class LabelsManagementRouter
{
    /**
     * @var IndexController
     */
    private $index_controller;
    /**
     * @var DeleteController
     */
    private $delete_controller;
    /**
     * @var EditController
     */
    private $edit_controller;
    /**
     * @var AddController
     */
    private $add_controller;

    public function __construct(
        IndexController $index_controller,
        DeleteController $delete_controller,
        EditController $edit_controller,
        AddController $add_controller,
    ) {
        $this->index_controller  = $index_controller;
        $this->delete_controller = $delete_controller;
        $this->edit_controller   = $edit_controller;
        $this->add_controller    = $add_controller;
    }

    public function process(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'delete':
                $this->delete_controller->delete($request);
                break;
            case 'edit':
                $this->edit_controller->edit($request);
                break;
            case 'add':
                $this->add_controller->add($request);
                break;
            default:
                $this->index_controller->display($request);
        }
    }
}
