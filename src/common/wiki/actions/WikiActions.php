<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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

require_once __DIR__ . '/../lib/WikiPageWrapper.php';

class WikiActions extends Actions
{
    public function __construct($controler)
    {
        parent::__construct($controler);
    }

    public function add_temp_page()
    {
        /* ADD TEST TO NOT ADD A ALREADY existing PAge */
        $wpw = new WikiPageWrapper($this->_controler->gid);
        $wpw->addNewProjectPage($_POST['name']);
    }
}
