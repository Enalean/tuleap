<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Templating_Presenter_SplitButtonDropdowns
{

    private $id;

    /**
     * @var Templating_Presenter_ButtonDropdownsOption
     */
    private $default_option;

    private $btn_class;

    /**
     * @var Templating_Presenter_ButtonDropdownsOption[]
     */
    private $options;

    public function __construct($id, $btn_class, Templating_Presenter_ButtonDropdownsOption $default_option, array $options)
    {
        $this->id = $id;
        $this->btn_class = $btn_class;
        $this->default_option = $default_option;
        $this->options = $options;
    }

    public function id()
    {
        return $this->id;
    }

    public function btn_class()
    {
        return $this->btn_class;
    }

    public function default_option()
    {
        return $this->default_option;
    }

    public function options()
    {
        return $this->options;
    }

    public function has_options()
    {
        return count($this->options) > 0;
    }
}
