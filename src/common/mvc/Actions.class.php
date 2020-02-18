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

/**
 * @template ActionsController of Controler
 */
class Actions
{

    /**
     * @var Controler
     * @psalm-var ActionsController
     */
    public $_controler;

    /**
     * @psalm-param ActionsController $controler
     */
    public function __construct($controler)
    {
        $this->_controler = $controler;
    }

    public function getControler()
    {
        return $this->_controler;
    }

    public function check()
    {
        return true;
    }

    public function process($action, $params = array())
    {
        if ($this->check()) {
            $this->$action($params);
        }
    }
}
